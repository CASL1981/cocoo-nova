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
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('pending_service_point_id')->nullable()->after('service_point_id')
                ->comment('Preferencia de visualización de pendientes: vacío muestra todas las sedes; con valor, solo esa sede destino.')
                ->constrained('service_points')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('pending_service_point_id');
        });
    }
};
