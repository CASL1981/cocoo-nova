<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cargo del funcionario en cada contacto del FO-300-01. El formato lo pide además del contacto
 * fijo (jefe comercial, facturación, cartera...). Es obligatorio: los contactos ya registrados
 * quedan con el texto por defecto "No informado".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provider_version_contacts', function (Blueprint $table): void {
            $table->string('job_title', 100)->default('No informado')->after('name')
                ->comment('Cargo del funcionario en la empresa del proveedor ("No informado" si el formato no lo trae).');
        });
    }

    public function down(): void
    {
        Schema::table('provider_version_contacts', function (Blueprint $table): void {
            $table->dropColumn('job_title');
        });
    }
};
