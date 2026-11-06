<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_legacy_updates', function (Blueprint $table): void {
            $table->id()->comment('Identificador.');
            $table->foreignId('provider_id')->comment('Proveedor que actualizó.')->constrained('providers')->cascadeOnDelete();
            $table->unsignedSmallInteger('year')->comment('Año de la actualización en el sistema anterior.');
            $table->date('updated_on')->nullable()->comment('Fecha de la actualización, si se conoce.');
            $table->auditColumns();

            $table->unique(['provider_id', 'year']);
            $table->comment('Actualizaciones del FO-300-01 hechas en el sistema anterior (solo la fecha, sin el formato digital). Se cargan con la carga inicial y se muestran como años informativos en el detalle del proveedor.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_legacy_updates');
    }
};
