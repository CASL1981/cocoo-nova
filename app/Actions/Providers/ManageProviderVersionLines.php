<?php

declare(strict_types=1);

namespace App\Actions\Providers;

use App\Enums\IdentityDocumentType;
use App\Enums\PersonType;
use App\Models\ProviderVersion;
use App\Models\ProviderVersionDocument;
use App\Models\ProviderVersionPepRelative;
use App\Models\ProviderVersionShareholder;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Punto único de escritura de las líneas de una versión: accionistas, familiares PEP y anexos.
 * Todas exigen que la versión siga en Borrador (la foto aprobada es inmutable).
 */
final class ManageProviderVersionLines
{
    /** Participación total máxima declarada entre todos los accionistas. */
    private const MAX_TOTAL_OWNERSHIP = 100;

    /** @param array<string, mixed> $data */
    public function addShareholder(ProviderVersion $version, array $data): ProviderVersionShareholder
    {
        return DB::transaction(function () use ($version, $data): ProviderVersionShareholder {
            $version = $this->lockDraft($version);

            if ($version->person_type !== PersonType::Legal) {
                throw ValidationException::withMessages(['name' => 'Los accionistas solo se registran para personas jurídicas.']);
            }

            // Representante legal solo para accionistas persona jurídica (NIT), y en ese caso obligatorio.
            if (($data['document_type'] ?? null) === IdentityDocumentType::Nit->value) {
                if (blank($data['legal_representative_name'] ?? null) || blank($data['legal_representative_identification'] ?? null)) {
                    throw ValidationException::withMessages(['legal_representative_name' => 'El accionista con NIT es una persona jurídica: ingrese el nombre y la cédula de su representante legal.']);
                }
            } else {
                $data['legal_representative_name'] = $data['legal_representative_identification'] = null;
            }

            $total = (float) $version->shareholders()->sum('ownership_percentage') + (float) $data['ownership_percentage'];

            if ($total > self::MAX_TOTAL_OWNERSHIP) {
                throw ValidationException::withMessages(['ownership_percentage' => 'La participación total de los accionistas no puede superar el 100 %.']);
            }

            return $version->shareholders()->create($data);
        });
    }

    public function removeShareholder(ProviderVersionShareholder $shareholder): void
    {
        DB::transaction(function () use ($shareholder): void {
            $this->lockDraft($shareholder->version);
            $shareholder->delete();
        });
    }

    /** @param array<string, mixed> $data */
    public function addPepRelative(ProviderVersion $version, array $data): ProviderVersionPepRelative
    {
        return DB::transaction(fn (): ProviderVersionPepRelative => $this->lockDraft($version)->pepRelatives()->create($data));
    }

    public function removePepRelative(ProviderVersionPepRelative $relative): void
    {
        DB::transaction(function () use ($relative): void {
            $this->lockDraft($relative->version);
            $relative->delete();
        });
    }

    /**
     * Guarda el archivo en el disco privado y registra el anexo.
     *
     * @param  array{type: string, issued_on: ?string}  $data
     */
    public function addDocument(ProviderVersion $version, array $data, UploadedFile $file, User $actor): ProviderVersionDocument
    {
        $path = $file->store("providers/{$version->provider_id}", ProviderVersionDocument::DISK)
            ?: throw new RuntimeException('No se pudo guardar el anexo del proveedor.');

        try {
            return DB::transaction(fn (): ProviderVersionDocument => $this->lockDraft($version)->documents()->create([
                ...$data,
                'path' => $path,
                'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
                'uploaded_by' => $actor->id,
            ]));
        } catch (\Throwable $e) {
            // Sin registro no debe quedar un archivo huérfano.
            Storage::disk(ProviderVersionDocument::DISK)->delete($path);

            throw $e;
        }
    }

    /** Quita el anexo; el archivo solo se borra si ninguna otra versión lo referencia. */
    public function removeDocument(ProviderVersionDocument $document): void
    {
        DB::transaction(function () use ($document): void {
            $this->lockDraft($document->version);
            $document->delete();
        });

        if (! ProviderVersionDocument::where('path', $document->path)->exists()) {
            Storage::disk(ProviderVersionDocument::DISK)->delete($document->path);
        }
    }

    private function lockDraft(ProviderVersion $version): ProviderVersion
    {
        $locked = ProviderVersion::query()->lockForUpdate()->findOrFail($version->id);

        if (! $locked->isDraft()) {
            throw ValidationException::withMessages(['version' => 'La versión ya no está en Borrador: sus líneas no se pueden modificar.']);
        }

        return $locked;
    }
}
