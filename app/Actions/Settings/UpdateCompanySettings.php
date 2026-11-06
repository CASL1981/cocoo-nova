<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Settings\CompanySettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Actualiza los datos de la empresa. Punto único de escritura de CompanySettings:
 * reemplaza o quita el logo y borra el archivo anterior para no dejar huérfanos.
 */
final class UpdateCompanySettings
{
    public function __construct(private readonly CompanySettings $settings) {}

    /** @param array<string, mixed> $data */
    public function execute(array $data, ?UploadedFile $logo = null, bool $removeLogo = false): CompanySettings
    {
        $previousLogo = $this->settings->logo_path;

        $this->settings->fill($data);

        if ($logo) {
            // store() devuelve false si el disco no pudo escribir el archivo: no se guarda una ruta inválida.
            $this->settings->logo_path = $logo->store('company', 'public')
                ?: throw new RuntimeException('No se pudo guardar el logo de la empresa.');
        } elseif ($removeLogo) {
            $this->settings->logo_path = null;
        }

        $this->settings->save();

        if ($previousLogo && $previousLogo !== $this->settings->logo_path) {
            Storage::disk('public')->delete($previousLogo);
        }

        return $this->settings;
    }
}
