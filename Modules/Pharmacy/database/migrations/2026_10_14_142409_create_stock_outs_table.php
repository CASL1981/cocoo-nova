<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pharmacy_stock_outs', function (Blueprint $table): void {
            $table->id()->comment('Identificador del registro de agotado.');
            $table->string('generic_name')->comment('Nombre genérico del medicamento reportado como agotado.');
            $table->string('product_name')->comment('Nombre comercial del producto agotado.');
            $table->string('brand', 150)->nullable()->comment('Laboratorio o marca del producto.');
            $table->string('classification', 150)->nullable()->comment('Clasificación del agotado según la circular.');
            $table->string('circular_number', 50)->comment('Número de la circular que reporta el agotado.');
            $table->date('circular_date')->comment('Fecha de emisión de la circular.');
            $table->string('generic_code', 50)->comment('Código genérico para homologar con el ERP y detectar agotados automáticamente.');
            $table->string('status', 20)->default('active')->comment('Activo mientras persiste el desabastecimiento; Compras lo inactiva manualmente.');

            $table->timestamp('created_at')->nullable()->comment('Fecha de creación del registro.');
            $table->timestamp('updated_at')->nullable()->comment('Fecha de la última modificación.');
            $table->softDeletes()->comment('Fecha de eliminación lógica.');
            $table->unsignedBigInteger('created_by')->nullable()->index()->comment('Usuario que creó el registro.');
            $table->unsignedBigInteger('updated_by')->nullable()->index()->comment('Usuario que modificó el registro por última vez.');
            $table->unsignedBigInteger('deleted_by')->nullable()->index()->comment('Usuario que eliminó el registro.');

            // Búsqueda frecuente: detección de agotados activos al registrar solicitudes.
            $table->index(['generic_code', 'status']);
            $table->index('circular_number');

            $table->comment('Productos agotados reportados por circulares, mantenidos por Compras.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pharmacy_stock_outs');
    }
};
