<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cargo de quien firma cada paso del FO-300-01 (guardado al firmar, para que el formato lo conserve
 * aunque la persona cambie de cargo) y aprobación de Gerencia de los proveedores PEP, en físico
 * (registro informativo) o en el sistema según Datos de la empresa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provider_versions', function (Blueprint $table): void {
            $table->string('submitted_position', 150)->nullable()->after('submitted_at')->comment('Cargo de quien envió a verificación, al momento de firmar.');
            $table->string('verified_position', 150)->nullable()->after('verified_at')->comment('Cargo de quien verificó la información, al momento de firmar.');
            $table->string('compliance_position', 150)->nullable()->after('compliance_at')->comment('Cargo del oficial de cumplimiento al momento de firmar u observar.');
            $table->foreignId('management_by')->nullable()->after('approved_at')
                ->comment('Usuario que aprobó como Gerencia (en el sistema) o registró la firma física de Gerencia.')
                ->constrained('users')->nullOnDelete();
            $table->string('management_position', 150)->nullable()->after('management_by')->comment('Cargo de quien aprobó o registró la firma de Gerencia.');
            $table->boolean('management_in_system')->nullable()->after('management_position')
                ->comment('true: Gerencia aprobó en el sistema; false: se registró su firma física; null: sin aprobación de Gerencia.');
            $table->date('management_signed_on')->nullable()->after('management_in_system')->comment('Fecha de la aprobación de Gerencia (firma física o en el sistema).');
            $table->timestamp('management_registered_at')->nullable()->after('management_signed_on')->comment('Fecha en que se registró la aprobación de Gerencia.');
            $table->text('management_observations')->nullable()->after('management_registered_at')->comment('Observaciones de la aprobación de Gerencia.');
        });

        Schema::table('provider_version_approvals', function (Blueprint $table): void {
            $table->string('user_position', 150)->nullable()->after('user_id')->comment('Cargo del director (o su reemplazo) al momento de aprobar.');
        });
    }

    public function down(): void
    {
        Schema::table('provider_version_approvals', function (Blueprint $table): void {
            $table->dropColumn('user_position');
        });

        Schema::table('provider_versions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('management_by');
            $table->dropColumn(['submitted_position', 'verified_position', 'compliance_position', 'management_position',
                'management_in_system', 'management_signed_on', 'management_registered_at', 'management_observations']);
        });
    }
};
