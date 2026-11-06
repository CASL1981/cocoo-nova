<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo oficial CIIU Rev. 4 A.C. (DIAN), de solo lectura: se carga con ReferenceCatalogSeeder
 * desde database/data/ciiu.csv y no lleva auditoría ni eliminación lógica.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('economic_activities', function (Blueprint $table): void {
            $table->comment('Actividades económicas CIIU Rev. 4 adaptada para Colombia (DIAN), incluidos los códigos especiales 0010, 0081, 0082 y 0090.');
            $table->id()->comment('Identificador de la actividad.');
            $table->char('code', 4)->unique()->comment('Código CIIU de cuatro dígitos.');
            $table->string('description', 255)->comment('Descripción oficial de la actividad.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('economic_activities');
    }
};
