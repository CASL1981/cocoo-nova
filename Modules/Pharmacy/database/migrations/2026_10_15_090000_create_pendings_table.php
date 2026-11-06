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
        Schema::create('pharmacy_pendings', function (Blueprint $table): void {
            $table->id()->comment('Identificador del pendiente.');
            $table->string('type', 2)->comment('Tipo de solicitud: PD = Pedido, RQ = Requisición, MI = Mensaje interno.');
            $table->string('category', 150)->comment('Categoría del pendiente (texto de la característica pending_category).');
            $table->foreignId('destination_id')->comment('Sede destino; la sede código 1 (Oficina Principal) indica que abarca varias sedes.')
                ->constrained('service_points')->restrictOnDelete();
            $table->string('reason')->nullable()->comment('Motivo corto de la solicitud.');
            $table->string('duration', 150)->nullable()->comment('Duración de la solicitud, en texto libre.');
            $table->text('observations')->nullable()->comment('Observaciones generales de la solicitud.');
            $table->string('eps', 150)->nullable()->comment('EPS asociada (texto de la característica eps, no FK).');
            $table->string('contracting_modality', 150)->nullable()->comment('Modalidad de contratación (texto de la característica contracting_modality, no FK).');
            $table->string('invoicing_method', 150)->nullable()->comment('Forma de facturación (texto de la característica invoicing_method, no FK).');
            $table->foreignId('user_id')->comment('Funcionario solicitante; es quien puede terminar la solicitud sin permiso adicional.')
                ->constrained('users')->restrictOnDelete();
            $table->string('manager', 150)->nullable()->comment('Persona externa que autoriza la solicitud.');
            $table->string('status', 20)->default('active')->comment('Activo mientras farmacia la arma; Terminado bloquea la edición de farmacia.');

            $table->auditColumns();

            // Consulta frecuente: pendientes por sede y estado (preferencia de visualización del usuario).
            $table->index(['destination_id', 'status']);

            $table->comment('Encabezado de pendientes de farmacia (pedidos, requisiciones y mensajes internos).');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pharmacy_pendings');
    }
};
