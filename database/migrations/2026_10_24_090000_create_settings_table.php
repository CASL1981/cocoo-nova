<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla de spatie/laravel-settings: configuración global editable agrupada por clase
 * de settings (ej. company = datos de la empresa para formatos y PDF).
 * Estructura del paquete; solo se agregan comentarios según la convención del proyecto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('settings.repositories.database.table') ?? 'settings', function (Blueprint $table): void {
            $table->comment('Configuración global de la aplicación (spatie/laravel-settings), un registro por propiedad.');
            $table->id()->comment('Identificador de la propiedad de configuración.');
            $table->string('group')->comment('Grupo de configuración (ej. company).');
            $table->string('name')->comment('Nombre de la propiedad dentro del grupo.');
            $table->boolean('locked')->default(false)->comment('Bloquea la propiedad para que no se modifique.');
            $table->json('payload')->comment('Valor de la propiedad en JSON.');
            $table->timestamp('created_at')->nullable()->comment('Fecha de creación del registro.');
            $table->timestamp('updated_at')->nullable()->comment('Fecha de la última modificación.');

            $table->unique(['group', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('settings.repositories.database.table') ?? 'settings');
    }
};
