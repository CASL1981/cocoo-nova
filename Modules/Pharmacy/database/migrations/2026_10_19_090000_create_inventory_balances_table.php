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
        Schema::create('pharmacy_inventory_balances', function (Blueprint $table): void {
            $table->id()->comment('Identificador del saldo.');
            $table->string('item', 50)->unique()->comment('Ítem del ERP (columna ProductId del archivo); coincide con shopping_products.item.');
            $table->string('product_name')->nullable()->comment('Nombre del producto según el ERP (columna ProductName).');
            $table->foreignId('product_id')->nullable()->comment('Producto homologado del catálogo; null si el ítem no existe en el catálogo.')
                ->constrained('shopping_products')->nullOnDelete();
            $table->string('generic_code', 50)->nullable()->index()->comment('Código genérico del producto homologado (snapshot); clave del cruce con los ítems de pendientes.');
            $table->unsignedInteger('available_quantity')->comment('Cantidad disponible cargada (suma de la columna Disponible del ERP por ítem).');
            $table->unsignedInteger('assigned_quantity')->default(0)->comment('Cantidad asignada a ítems de pendientes desde esta carga; saldo = disponible − asignado.');
            $table->date('cut_off_date')->comment('Fecha de corte de la información cargada (la digita el usuario).');

            // Sin eliminación lógica (decisión de negocio): cada carga reemplaza por completo la anterior.
            $table->timestamp('created_at')->nullable()->comment('Fecha y hora de la carga; las asignaciones posteriores descuentan del saldo.');
            $table->timestamp('updated_at')->nullable()->comment('Fecha de la última modificación (asignaciones).');
            $table->unsignedBigInteger('created_by')->nullable()->index()->comment('Usuario que realizó la carga.');

            $table->comment('Inventario disponible de Oficina Principal cargado desde el ERP (última carga vigente).');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pharmacy_inventory_balances');
    }
};
