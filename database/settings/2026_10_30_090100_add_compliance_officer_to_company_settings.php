<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Oficial de cumplimiento: revisa a los proveedores PEP antes de que Gerencia los apruebe.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('company.compliance_officer_id', null);
    }
};
