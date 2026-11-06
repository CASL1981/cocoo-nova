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
        Schema::create('pharmacy_pending_items', function (Blueprint $table): void {
            $table->id()->comment('Identificador del ítem.');
            $table->foreignId('pending_id')->comment('Encabezado del pendiente al que pertenece el ítem.')
                ->constrained('pharmacy_pendings')->restrictOnDelete();
            $table->foreignId('product_id')->comment('Producto solicitado del catálogo de Compras.')
                ->constrained('shopping_products')->restrictOnDelete();
            $table->string('product_name')->comment('Descripción del producto al momento de la solicitud (snapshot).');
            $table->string('generic_code', 50)->comment('Código genérico del producto (snapshot); se cruza con los agotados.');
            $table->string('generic_name')->comment('Nombre genérico del producto (snapshot).');
            $table->string('brand')->nullable()->comment('Marca que escribe el solicitante; no se toma del producto.');
            // Código de la sede (no FK): el archivo de importación trae el código, no el id interno.
            $table->string('destination_code', 20)->comment('Código de la sede destino (service_points.code); solo Oficina Principal registra destinos distintos al del encabezado.');
            $table->unsignedInteger('quantity')->comment('Cantidad solicitada.');
            $table->unsignedInteger('send_quantity')->nullable()->comment('Cantidad enviada; la asigna Oficina Principal.');
            $table->date('date_send')->nullable()->comment('Fecha en que Oficina Principal asignó la cantidad enviada.');
            $table->string('doc_number', 50)->nullable()->comment('Número TB del ERP u orden de compra.');
            $table->unsignedInteger('number_day')->nullable()->comment('Días entre la creación y el envío; se calcula al pasar a Cumplido.');
            $table->string('circular_number', 50)->nullable()->comment('Número de circular del agotado vigente al registrar el ítem (texto, no FK).');
            $table->text('observations')->nullable()->comment('Observaciones del ítem.');
            $table->string('status', 20)->default('unreviewed')->comment('Sin revisión, Cumplido, Parcial, Pendiente o Agotado (definitivo).');

            // Sin auditoría ni eliminación lógica (decisión de negocio): el ítem eliminado se borra
            // definitivamente; la trazabilidad la conserva el encabezado (pharmacy_pendings).
            $table->timestamp('created_at')->nullable()->comment('Fecha de registro del ítem; base del cálculo de number_day.');
            $table->timestamp('updated_at')->nullable()->comment('Fecha de la última modificación.');

            $table->index(['pending_id', 'status']);
            // Vista de trabajo de Oficina Principal: ítems por estado de todas las sedes.
            $table->index(['status', 'destination_code']);

            $table->comment('Ítems (detalle) de los pendientes de farmacia.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pharmacy_pending_items');
    }
};
