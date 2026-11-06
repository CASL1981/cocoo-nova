<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Enums\ProviderDocumentType;
use App\Livewire\Concerns\SanitizesPayload;
use Illuminate\Validation\Rule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Form;

/**
 * Carga de un anexo del formato FO-300-01 (PDF o imagen, máximo 5 MB).
 */
final class ProviderDocumentForm extends Form
{
    use SanitizesPayload;

    /** Tamaño máximo del anexo en kilobytes. */
    public const MAX_SIZE_KB = 5120;

    public string $type = '';

    public ?string $issued_on = null;

    public ?TemporaryUploadedFile $file = null;

    /** @return array<string, array<int, mixed>> */
    protected function rules(): array
    {
        $type = ProviderDocumentType::tryFrom($this->type);

        return [
            'type' => ['required', Rule::enum(ProviderDocumentType::class)],
            // La cámara de comercio exige fecha de expedición para controlar su vigencia de 3 meses.
            'issued_on' => [$type?->requiresIssueDate() ? 'required' : 'nullable', 'date', 'before_or_equal:today'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:'.self::MAX_SIZE_KB],
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return ['type' => 'tipo de anexo', 'issued_on' => 'fecha de expedición', 'file' => 'archivo'];
    }

    /**
     * Datos del anexo sin el archivo (que se guarda aparte).
     *
     * @return array{type: string, issued_on: ?string}
     */
    public function payload(): array
    {
        $data = $this->sanitize($this->validate());

        return ['type' => $data['type'], 'issued_on' => $data['issued_on'] ?? null];
    }
}
