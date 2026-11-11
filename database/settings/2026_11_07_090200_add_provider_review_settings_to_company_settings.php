<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Revisión del FO-300-01:
 * - provider_pep_management_approval: la Gerencia aprueba a los proveedores PEP en físico
 *   (registro informativo, por defecto) o en el sistema.
 * - provider_verifier_positions: cargos (ids de Talento Humano → Cargos) que verifican cada tipo
 *   de proveedor; vacío = sin restricción por cargo. Los valores iniciales los pone ProviderVerifierSeeder.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('company.provider_pep_management_approval', 'physical');
        $this->migrator->add('company.provider_verifier_positions', ['administrative' => [], 'commercial' => []]);
    }
};
