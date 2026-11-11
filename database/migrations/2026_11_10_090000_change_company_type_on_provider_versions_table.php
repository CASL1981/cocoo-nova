<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El tipo de empresa deja de ser un enum fijo: pasa a la característica "provider_company_type"
 * (Configuración → Características), que los usuarios administran (ej. Unión temporal). Se guarda
 * el texto de la opción, como el régimen tributario. Además, "¿Posee cuentas en moneda extranjera?"
 * aplica ahora a persona natural y jurídica.
 */
return new class extends Migration
{
    /** Valores del enum anterior y su texto en la característica. */
    private const VALUES = ['public' => 'Pública', 'private' => 'Privada', 'mixed' => 'Mixta'];

    public function up(): void
    {
        Schema::table('provider_versions', function (Blueprint $table): void {
            $table->string('company_type', 100)->nullable()
                ->comment('Tipo de empresa (jurídica): texto de la opción de la característica provider_company_type.')->change();
            $table->boolean('has_foreign_accounts')->default(false)
                ->comment('Posee cuentas en moneda extranjera (natural y jurídica).')->change();
        });

        foreach (self::VALUES as $old => $new) {
            DB::table('provider_versions')->where('company_type', $old)->update(['company_type' => $new]);
        }
    }

    public function down(): void
    {
        foreach (self::VALUES as $old => $new) {
            DB::table('provider_versions')->where('company_type', $new)->update(['company_type' => $old]);
        }

        // Los valores que no existían en el enum (ej. Unión temporal) no caben en el esquema anterior.
        DB::table('provider_versions')->whereNotIn('company_type', array_keys(self::VALUES))->update(['company_type' => null]);

        Schema::table('provider_versions', function (Blueprint $table): void {
            $table->string('company_type', 10)->nullable()->comment('Tipo de empresa: public, private o mixed (jurídica).')->change();
            $table->boolean('has_foreign_accounts')->default(false)->comment('Posee cuentas en moneda extranjera (natural).')->change();
        });
    }
};
