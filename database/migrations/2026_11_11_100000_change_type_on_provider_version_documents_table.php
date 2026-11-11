<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El tipo de anexo del FO-300-01 deja de ser un enum fijo: pasa a la característica
 * "provider_document_type" (Utilidades → Características), que administran los usuarios. Se guarda
 * el texto de la opción, como el tipo de empresa.
 */
return new class extends Migration
{
    /** Valores del enum anterior y su texto en la característica. */
    private const VALUES = [
        'identity_document' => 'Documento de identificación',
        'chamber_of_commerce' => 'Certificado de existencia y representación legal',
        'rut' => 'Registro único tributario (RUT)',
        'return_policy' => 'Políticas de devolución',
        'signed_form' => 'Formulario FO-300-01 firmado',
        'other' => 'Otro',
    ];

    public function up(): void
    {
        Schema::table('provider_version_documents', function (Blueprint $table): void {
            $table->string('type', 150)
                ->comment('Tipo de anexo: texto de la opción de la característica provider_document_type.')->change();
        });

        foreach (self::VALUES as $old => $new) {
            DB::table('provider_version_documents')->where('type', $old)->update(['type' => $new]);
        }
    }

    public function down(): void
    {
        foreach (self::VALUES as $old => $new) {
            DB::table('provider_version_documents')->where('type', $new)->update(['type' => $old]);
        }

        // Los tipos creados después (que no existían en el enum) quedan como "other".
        DB::table('provider_version_documents')->whereNotIn('type', array_keys(self::VALUES))->update(['type' => 'other']);

        Schema::table('provider_version_documents', function (Blueprint $table): void {
            $table->string('type', 30)->comment('Tipo de anexo (ProviderDocumentType).')->change();
        });
    }
};
