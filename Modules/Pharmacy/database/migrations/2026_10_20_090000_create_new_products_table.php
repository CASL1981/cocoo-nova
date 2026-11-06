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
        Schema::create('pharmacy_new_products', function (Blueprint $table): void {
            $table->id()->comment('Identificador de la solicitud de producto nuevo.');
            $table->string('product_name')->comment('Nombre del producto tal como lo escribe la farmacia (aún sin codificar).');
            $table->string('normalized_name')->comment('Nombre normalizado (mayúsculas, sin tildes ni espacios repetidos) para detectar duplicados.');
            $table->string('brand', 150)->nullable()->comment('Marca del producto, si la farmacia la conoce.');
            $table->unsignedInteger('quantity')->comment('Cantidad solicitada.');
            // Código de la sede (no FK), igual que en los ítems de pendientes.
            $table->string('destination_code', 20)->comment('Código de la farmacia solicitante (service_points.code).');
            $table->foreignId('user_id')->comment('Funcionario que registró la solicitud.')
                ->constrained('users')->restrictOnDelete();
            $table->foreignId('product_id')->nullable()->comment('Ítem del catálogo de Compras asociado al codificarse; obligatorio para asignar unidades.')
                ->constrained('shopping_products')->restrictOnDelete();
            $table->unsignedInteger('send_quantity')->nullable()->comment('Unidades enviadas; Oficina Principal las asigna manualmente (no descuenta el saldo del ERP).');
            $table->date('date_send')->nullable()->comment('Fecha de la última asignación de unidades.');
            $table->string('doc_number', 50)->nullable()->comment('Número TB del ERP; solo en solicitudes Cumplidas.');
            $table->text('observations')->nullable()->comment('Observaciones de la solicitud.');
            $table->string('status', 20)->default('unassigned')->comment('Sin asignar, Parcial, Cumplido o Rechazado.');

            $table->auditColumns();

            // Bloqueo de duplicados: nombre ya asociado o solicitud abierta en la misma farmacia.
            $table->index(['normalized_name', 'destination_code']);
            $table->index(['status', 'destination_code']);

            $table->comment('Solicitudes de productos nuevos (sin codificación en el inventario) de las farmacias.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pharmacy_new_products');
    }
};
