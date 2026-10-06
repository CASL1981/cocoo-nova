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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('item', 50)->unique()->comment('Código/ítem del producto');
            $table->string('description', 255)->comment('Descripción del producto');
            $table->string('brand')->nullable()->comment('Marca del producto');
            $table->string('atc', 20)->nullable()->comment('Clasificación ATC del medicamento');
            $table->string('invima', 50)->nullable()->comment('Registro sanitario INVIMA');
            $table->string('measure_unit', 50)->comment('Unidad de medida (caja, frasco, tableta...)');
            $table->string('presentation')->nullable()->comment('Presentación del producto');     // ej. "Caja x 100"
            $table->string('concentration')->nullable()->comment('Concentración del producto');    // ej. "500 mg"
            $table->string('pharmaceutical_form')->nullable()->comment('Forma farmacéutica');
            $table->string('generic_name')->comment('Nombre genérico');
            $table->string('generic_code', 50)->comment('Código genérico');
            $table->decimal('tax_percentage', 5, 2)->nullable()->comment('Porcentaje de impuesto');
            $table->string('status', 20)->default('active')->comment('Estado del producto');

            // Mismo estándar que el resto del proyecto desde la Fase de permisos:
            $table->softDeletes();
            $table->userstamps();
            $table->userstampSoftDeletes();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
