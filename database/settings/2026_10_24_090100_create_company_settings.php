<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Valores iniciales de los datos de la empresa; se completan desde la pantalla de configuración.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('company.legal_name', 'COODESCOR');
        $this->migrator->add('company.nit', '812001561');
        $this->migrator->add('company.address', null);
        $this->migrator->add('company.city', null);
        $this->migrator->add('company.phone', null);
        $this->migrator->add('company.email', null);
        $this->migrator->add('company.website', null);
        $this->migrator->add('company.logo_path', null);
    }
};
