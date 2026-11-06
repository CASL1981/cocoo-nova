<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Proveedores básicos (sin formato FO-300-01).
 *
 * El FO-300-01 solo lo diligencian los proveedores con una orden de compra, o un acumulado
 * mensual por proceso, mayor a 2 SMMLV. Los demás se registran con datos mínimos en el propio
 * proveedor. Al aprobarse una versión del formato, estos datos se actualizan desde ella.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('providers', function (Blueprint $table): void {
            $table->string('email', 150)->nullable()->after('name')->comment('Correo principal del proveedor (el del representante legal si no hay otro).');
            $table->string('phone', 50)->nullable()->after('email')->comment('Teléfono o celular del proveedor.');
            $table->string('address', 255)->nullable()->after('phone')->comment('Dirección del domicilio o sede principal.');
            $table->foreignId('municipality_id')->nullable()->after('address')->comment('Municipio del domicilio o sede principal (DIVIPOLA).')
                ->constrained('geographic_locations')->restrictOnDelete();
            $table->string('contact_name', 150)->nullable()->after('municipality_id')->comment('Persona de contacto del proveedor.');
        });

        Schema::table('provider_linkages', function (Blueprint $table): void {
            $table->timestamp('form_required_at')->nullable()->after('payment_term')
                ->comment('Fecha en que una orden de compra superó el tope (2 SMMLV) sin FO-300-01 vigente.');
            $table->string('form_required_reason', 255)->nullable()->after('form_required_at')
                ->comment('Orden o acumulado mensual que hizo exigible el FO-300-01.');
        });

        // Datos básicos desde la versión vigente del formato.
        DB::table('providers')->whereNotNull('current_version_id')->orderBy('id')->each(function (object $provider): void {
            $version = DB::table('provider_versions')->where('id', $provider->current_version_id)->first();

            DB::table('providers')->where('id', $provider->id)->update([
                'email' => $version?->email,
                'phone' => $version?->phone,
                'address' => $version?->address,
                'municipality_id' => $version?->municipality_id,
            ]);
        });

        // La carga inicial del sistema anterior creó versiones "aprobadas" solo con datos mínimos
        // (sin envío ni verificación). Esos proveedores son básicos: se quitan esas versiones de relleno.
        $placeholders = DB::table('provider_versions')
            ->where('status', 'approved')->whereNull('submitted_at')->whereNull('verified_at')
            ->pluck('id');

        if ($placeholders->isNotEmpty()) {
            DB::table('providers')->whereIn('current_version_id', $placeholders)
                ->update(['current_version_id' => null, 'risk_level' => null, 'is_pep' => false, 'next_update_on' => null]);
            DB::table('provider_versions')->whereIn('id', $placeholders)->delete();
        }
    }

    public function down(): void
    {
        Schema::table('provider_linkages', function (Blueprint $table): void {
            $table->dropColumn(['form_required_at', 'form_required_reason']);
        });

        Schema::table('providers', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('municipality_id');
            $table->dropColumn(['email', 'phone', 'address', 'contact_name']);
        });
    }
};
