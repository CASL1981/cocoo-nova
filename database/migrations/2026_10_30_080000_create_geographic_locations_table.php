<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ubicación geográfica en una sola tabla jerárquica: país → departamento → ciudad.
 *
 * La mantienen los usuarios en Configuración → Ubicación geográfica. La carga inicial
 * (ISO 3166-1 y DIVIPOLA del DANE) la hace ReferenceCatalogSeeder.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geographic_locations', function (Blueprint $table): void {
            $table->comment('Países, departamentos y ciudades en una sola jerarquía (país → departamento → ciudad).');
            $table->id()->comment('Identificador de la ubicación.');
            $table->string('level', 20)->comment('Nivel: country (país), department (departamento) o city (ciudad).');
            $table->foreignId('parent_id')->nullable()->comment('Ubicación a la que pertenece: el país de un departamento o el departamento de una ciudad.')
                ->constrained('geographic_locations')->restrictOnDelete();
            $table->string('code', 20)->unique()->comment('Código único: ISO 3166-1 alfa-2 en países (CO), DANE en departamentos (23) y ciudades (23001); ISO 3166-2 o con prefijo del país en el exterior.');
            $table->string('name', 150)->comment('Nombre de la ubicación.');
            $table->unsignedBigInteger('population')->nullable()->comment('Población (opcional, para uso futuro).');
            $table->string('status', 20)->default('active')->comment('Estado: active o inactive (inactiva no se ofrece en los formularios).');
            $table->auditColumns();

            $table->index(['level', 'parent_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geographic_locations');
    }
};
