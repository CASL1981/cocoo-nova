<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Salario mínimo mensual legal vigente (SMMLV) por año y múltiplo que hace exigible el
 * formato FO-300-01 a un proveedor (orden o acumulado mensual mayor a 2 SMMLV).
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('company.minimum_wages', ['2026' => 1750905]);
        $this->migrator->add('company.provider_form_wage_multiplier', 2);
    }
};
