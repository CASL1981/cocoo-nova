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
        Schema::create('service_points', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 150);
            $table->string('type', 20)->default('other'); // headquarters | pharmacy | other
            $table->string('address')->nullable();
            $table->string('phone', 30)->nullable();
            // minimum/maximum: umbrales de inventario que usará el módulo de Farmacia
            // más adelante para detectar "productos pendientes"; por ahora quedan null.
            $table->unsignedInteger('minimum')->nullable();
            $table->unsignedInteger('maximum')->nullable();
            $table->string('status', 20)->default('active');
            $table->softDeletes();        // deleted_at (Laravel)
            $table->userstamps();         // created_by, updated_by (paquete)
            $table->userstampSoftDeletes(); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_points');
    }
};
