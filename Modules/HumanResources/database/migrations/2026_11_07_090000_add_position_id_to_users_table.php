<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cargo del usuario (Talento Humano → Cargos). Se muestra en las firmas del FO-300-01 y limita
 * quién verifica proveedores (Datos de la empresa → cargos que verifican).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('position_id')->nullable()->after('process_id')
                ->comment('Cargo del usuario: opción de la característica Cargos de Talento Humano.')
                ->constrained('hr_characteristic_options')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('position_id');
        });
    }
};
