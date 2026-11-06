<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Características de Talento Humano (listas configurables del área) y sus opciones.
 *
 * Prefijo "hr_" (excepción documentada a la regla de nombres: abreviatura de HumanResources).
 * Las opciones de la característica "Cargos" (code = positions) usan además el proceso al que
 * pertenece el cargo y su jefe inmediato (otra opción de la misma característica).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_characteristics', function (Blueprint $table): void {
            $table->comment('Listas configurables de Talento Humano (ej. cargos); separadas de las características generales.');
            $table->id()->comment('Identificador de la característica.');
            $table->string('name', 150)->comment('Nombre visible de la característica.');
            $table->string('code', 100)->unique()->comment('Código interno en snake_case con el que la aplicación busca la lista (ej. positions).');
            $table->string('status', 20)->default('active')->comment('Estado: active o inactive.');
            $table->auditColumns();
        });

        Schema::create('hr_characteristic_options', function (Blueprint $table): void {
            $table->comment('Opciones de una característica de Talento Humano (ej. cada cargo del organigrama).');
            $table->id()->comment('Identificador de la opción.');
            $table->foreignId('hr_characteristic_id')->comment('Característica a la que pertenece la opción.')
                ->constrained('hr_characteristics')->restrictOnDelete();
            $table->string('name', 150)->comment('Texto de la opción (ej. Coordinador de Compras).');
            $table->foreignId('process_id')->nullable()->comment('Proceso (dirección) al que pertenece el cargo; solo en Cargos.')
                ->constrained('processes')->restrictOnDelete();
            $table->foreignId('parent_id')->nullable()->comment('Jefe inmediato: otra opción de la misma característica; solo en Cargos.')
                ->constrained('hr_characteristic_options')->restrictOnDelete();
            $table->string('status', 20)->default('active')->comment('Estado: active o inactive.');
            $table->auditColumns();

            $table->unique(['hr_characteristic_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_characteristic_options');
        Schema::dropIfExists('hr_characteristics');
    }
};
