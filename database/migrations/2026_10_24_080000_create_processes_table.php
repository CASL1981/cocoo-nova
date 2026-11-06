<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Procesos (áreas) de la organización y su vínculo con los usuarios.
 *
 * Reemplaza el texto libre users.area: el proceso define quién aprueba las
 * requisiciones del módulo Administrativo (su director o un reemplazo).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('processes', function (Blueprint $table): void {
            $table->comment('Procesos (áreas) de la organización; cada uno con un director que aprueba sus requisiciones.');
            $table->id()->comment('Identificador del proceso.');
            $table->string('name', 100)->unique()->comment('Nombre del proceso (ej. Gerencia, Administrativo).');
            $table->foreignId('director_id')->nullable()->comment('Usuario director del proceso; aprueba las requisiciones de sus integrantes.')
                ->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('active')->comment('Estado del proceso: active o inactive.');
            $table->auditColumns();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('process_id')->nullable()->after('lastname')
                ->comment('Proceso (área) al que pertenece el usuario.')
                ->constrained('processes')->nullOnDelete();
            $table->boolean('can_request_supplies')->default(false)->after('process_id')
                ->comment('Habilita al usuario para hacer requisiciones de aseo, papelería y cafetería; lo administra el Administrador.');
        });

        Schema::create('process_substitutions', function (Blueprint $table): void {
            $table->comment('Reemplazos de directores de proceso: el reemplazo aprueba junto con el titular durante la vigencia.');
            $table->id()->comment('Identificador del reemplazo.');
            $table->foreignId('process_id')->comment('Proceso cuyo director es reemplazado.')
                ->constrained('processes')->cascadeOnDelete();
            $table->foreignId('substitute_id')->comment('Usuario que aprueba en reemplazo del director.')
                ->constrained('users')->cascadeOnDelete();
            $table->date('starts_on')->comment('Fecha de inicio del reemplazo.');
            $table->date('ends_on')->nullable()->comment('Fecha de fin del reemplazo; vacía indica una asignación permanente.');
            $table->text('observations')->nullable()->comment('Motivo u observaciones del reemplazo (ej. vacaciones).');
            $table->auditColumns();
            $table->index(['process_id', 'starts_on', 'ends_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('process_substitutions');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('process_id');
            $table->dropColumn('can_request_supplies');
        });

        Schema::dropIfExists('processes');
    }
};
