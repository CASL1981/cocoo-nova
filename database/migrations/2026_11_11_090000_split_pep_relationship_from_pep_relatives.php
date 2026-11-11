<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Separa, como en el formato físico FO-300-01, dos datos que estaban mezclados:
 *
 * - "¿Tiene relación con PEP?": la persona PEP con la que el proveedor se relaciona (nombre,
 *   identificación, cargo, inicio, fin y relación) pasa a la versión (pep_relation_*).
 * - "Grupo básico familiar como PEP": cada familiar solo registra tipo y número de documento,
 *   nombres y apellidos y parentesco; se retiran sus columnas de cargo PEP.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provider_versions', function (Blueprint $table): void {
            $table->string('pep_relation_name', 255)->nullable()->after('has_pep_relationship')->comment('Nombre del PEP con quien tiene relación.');
            $table->string('pep_relation_identification', 20)->nullable()->after('pep_relation_name')->comment('Identificación del PEP con quien tiene relación.');
            $table->string('pep_relation_office', 150)->nullable()->after('pep_relation_identification')->comment('Cargo del PEP con quien tiene relación.');
            $table->date('pep_relation_starts_on')->nullable()->after('pep_relation_office')->comment('Fecha de inicio en el cargo del PEP relacionado.');
            $table->date('pep_relation_ends_on')->nullable()->after('pep_relation_starts_on')->comment('Fecha de finalización en el cargo del PEP relacionado; vacía si sigue vigente.');
            $table->string('pep_relation_relationship', 100)->nullable()->after('pep_relation_ends_on')->comment('Relación con el PEP, desde Características (provider_relationship).');
        });

        Schema::table('provider_version_pep_relatives', function (Blueprint $table): void {
            $table->dropColumn(['is_pep', 'public_office', 'pep_starts_on', 'pep_ends_on']);
        });
    }

    public function down(): void
    {
        Schema::table('provider_version_pep_relatives', function (Blueprint $table): void {
            $table->boolean('is_pep')->default(false)->after('relationship')->comment('La persona relacionada es PEP.');
            $table->string('public_office', 150)->nullable()->after('is_pep')->comment('Cargo PEP de la persona relacionada.');
            $table->date('pep_starts_on')->nullable()->after('public_office')->comment('Inicio en el cargo PEP.');
            $table->date('pep_ends_on')->nullable()->after('pep_starts_on')->comment('Fin del cargo PEP; vacía si sigue vigente.');
        });

        Schema::table('provider_versions', function (Blueprint $table): void {
            $table->dropColumn(['pep_relation_name', 'pep_relation_identification', 'pep_relation_office',
                'pep_relation_starts_on', 'pep_relation_ends_on', 'pep_relation_relationship']);
        });
    }
};
