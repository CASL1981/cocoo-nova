<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Proveedores (formato FO-300-01 de vinculación y actualización).
 *
 * El tercero es transversal y único por identificación; cada vinculación o actualización
 * es una versión histórica con la foto completa del formulario. Cada proceso
 * (Administrativo o Comercial) gestiona su propia vinculación con el tercero.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('providers', function (Blueprint $table): void {
            $table->comment('Terceros proveedores, únicos por identificación y compartidos por los procesos que les compran.');
            $table->id()->comment('Identificador del proveedor.');
            $table->string('person_type', 20)->comment('Tipo de persona: natural o legal (jurídica).');
            $table->string('document_type', 5)->comment('Tipo de documento: RC, CC, TI, CE, PAS, NIT o UT.');
            $table->string('identification', 20)->unique()->comment('Número de identificación sin dígito de verificación.');
            $table->string('name', 255)->index()->comment('Razón social o nombres y apellidos según la versión vigente.');
            $table->unsignedBigInteger('current_version_id')->nullable()->comment('Versión aprobada vigente del formulario FO-300-01.');
            $table->string('risk_level', 10)->nullable()->comment('Nivel de riesgo de la versión vigente: high, medium o low.');
            $table->boolean('is_pep')->default(false)->comment('La versión vigente lo califica como persona expuesta políticamente (PEP).');
            $table->date('next_update_on')->nullable()->comment('Fecha límite de la actualización anual (aprobación + 1 año).');
            $table->auditColumns();
        });

        Schema::create('provider_versions', function (Blueprint $table): void {
            $table->comment('Versiones históricas del formato FO-300-01: cada vinculación o actualización con la foto completa del formulario.');
            $table->id()->comment('Identificador de la versión.');
            $table->foreignId('provider_id')->comment('Proveedor al que pertenece la versión.')
                ->constrained('providers')->cascadeOnDelete();
            $table->unsignedSmallInteger('number')->comment('Consecutivo de la versión dentro del proveedor.');
            $table->string('request_type', 20)->comment('Tipo de solicitud: linkage (vinculación) o update (actualización).');
            $table->string('status', 20)->index()->comment('Estado: draft, verification, compliance, approval, approved o rejected.');

            // Identificación (foto del tercero en esta versión).
            $table->string('person_type', 20)->comment('Tipo de persona: natural o legal.');
            $table->string('document_type', 5)->comment('Tipo de documento del proveedor.');
            $table->string('identification', 20)->comment('Número de identificación sin dígito de verificación.');
            $table->string('name', 255)->comment('Razón social o nombres y apellidos.');
            $table->string('email', 150)->nullable()->comment('Correo electrónico del proveedor.');
            $table->string('phone', 50)->nullable()->comment('Teléfono o celular.');
            $table->string('address', 255)->nullable()->comment('Dirección de domicilio o de la sede principal.');
            $table->foreignId('department_id')->nullable()->comment('Departamento del domicilio o sede.')
                ->constrained('geographic_locations')->restrictOnDelete();
            $table->foreignId('municipality_id')->nullable()->comment('Municipio del domicilio o sede.')
                ->constrained('geographic_locations')->restrictOnDelete();
            $table->foreignId('country_id')->nullable()->comment('País de residencia (natural) o de constitución (jurídica).')
                ->constrained('geographic_locations')->restrictOnDelete();
            $table->foreignId('economic_activity_id')->nullable()->comment('Actividad económica principal (CIIU).')
                ->constrained('economic_activities')->nullOnDelete();
            $table->string('economic_activity_description', 255)->nullable()->comment('Descripción libre de la actividad económica.');

            // Persona natural.
            $table->date('document_issued_on')->nullable()->comment('Fecha de expedición del documento (natural).');
            $table->foreignId('nationality_country_id')->nullable()->comment('Nacionalidad, para CE o pasaporte (natural).')
                ->constrained('geographic_locations')->restrictOnDelete();
            $table->date('birth_date')->nullable()->comment('Fecha de nacimiento (natural).');
            $table->foreignId('birth_department_id')->nullable()->comment('Departamento de nacimiento (natural).')
                ->constrained('geographic_locations')->restrictOnDelete();
            $table->foreignId('birth_municipality_id')->nullable()->comment('Municipio de nacimiento (natural).')
                ->constrained('geographic_locations')->restrictOnDelete();
            $table->string('education_level', 100)->nullable()->comment('Nivel de educación, desde Características (natural).');
            $table->string('residence_zone', 10)->nullable()->comment('Zona del domicilio: urban o rural (natural).');
            $table->string('protection_group', 150)->nullable()->comment('Grupo de protección especial constitucional, desde Características (natural).');
            $table->string('protection_group_other', 150)->nullable()->comment('Otro grupo de protección especial, si aplica (natural).');
            $table->string('occupation', 100)->nullable()->comment('Ocupación, desde Características (natural).');
            $table->string('occupation_other', 150)->nullable()->comment('Otra ocupación, si aplica (natural).');
            $table->string('employer_name', 255)->nullable()->comment('Empresa donde labora, si aplica (natural).');
            $table->string('employer_activity', 255)->nullable()->comment('Actividad económica de la empresa donde labora (natural).');
            $table->foreignId('employer_economic_activity_id')->nullable()->comment('CIIU de la empresa donde labora (natural).')
                ->constrained('economic_activities')->nullOnDelete();
            $table->string('minor_representative_name', 255)->nullable()->comment('Representante legal o apoderado de un menor de edad (natural).');
            $table->string('minor_representative_identification', 20)->nullable()->comment('Identificación del representante del menor de edad (natural).');
            $table->boolean('has_foreign_accounts')->default(false)->comment('Posee cuentas en moneda extranjera (natural).');
            $table->boolean('accepts_electronic_means')->default(false)->comment('Acepta los mecanismos electrónicos de verificación de la Ley 527 de 1999 (natural).');

            // Persona jurídica.
            $table->string('tax_regime', 100)->nullable()->comment('Régimen tributario, desde Características (jurídica).');
            $table->string('company_type', 10)->nullable()->comment('Tipo de empresa: public, private o mixed (jurídica).');
            $table->string('legal_representative_name', 255)->nullable()->comment('Nombre completo del representante legal (jurídica).');
            $table->string('legal_representative_document_type', 5)->nullable()->comment('Tipo de documento del representante legal (jurídica).');
            $table->string('legal_representative_identification', 20)->nullable()->comment('Identificación del representante legal (jurídica).');
            $table->decimal('total_assets', 18, 2)->nullable()->comment('Total de activos (jurídica).');
            $table->decimal('total_liabilities', 18, 2)->nullable()->comment('Total de pasivos (jurídica).');
            $table->decimal('total_equity', 18, 2)->nullable()->comment('Total de patrimonio (jurídica).');
            $table->boolean('in_insolvency')->default(false)->comment('Está en un proceso de ley de insolvencia (jurídica).');
            $table->boolean('accepts_risk_bureau')->default(false)->comment('Autoriza la consulta y reporte en centrales de riesgo.');

            // Información financiera y moneda extranjera.
            $table->decimal('monthly_income', 18, 2)->nullable()->comment('Ingresos mensuales de la actividad principal.');
            $table->decimal('other_income', 18, 2)->nullable()->comment('Otros ingresos no operacionales.');
            $table->string('other_income_activity', 255)->nullable()->comment('Actividad de la que provienen los otros ingresos.');
            $table->boolean('foreign_currency_operations')->default(false)->comment('Realiza operaciones en moneda extranjera.');
            $table->string('foreign_currency_detail', 255)->nullable()->comment('Operaciones o monedas extranjeras que maneja.');

            // Personas expuestas políticamente (PEP).
            $table->boolean('manages_public_resources')->default(false)->comment('Administra recursos públicos.');
            $table->boolean('declares_pep')->default(false)->comment('Se declara persona expuesta políticamente.');
            $table->string('public_office', 150)->nullable()->comment('Cargo público PEP.');
            $table->date('pep_starts_on')->nullable()->comment('Fecha de inicio en el cargo PEP.');
            $table->date('pep_ends_on')->nullable()->comment('Fecha de finalización del cargo PEP; vacía si sigue vigente.');
            $table->boolean('has_pep_relationship')->default(false)->comment('Tiene relación (familiar o de otro tipo) con un PEP.');
            $table->boolean('is_pep')->default(false)->comment('Resultado calculado: el proveedor se considera PEP.');
            $table->string('risk_level', 10)->comment('Nivel de riesgo calculado: high, medium o low.');

            // Contactos y declaraciones.
            $table->text('dispatch_schedule')->nullable()->comment('Días y horarios de despacho de pedidos, si hay restricciones.');
            $table->boolean('accepts_truthfulness')->default(false)->comment('Declaración de veracidad de la información.');
            $table->boolean('accepts_data_processing')->default(false)->comment('Consentimiento para el tratamiento de datos personales (Ley 1581 de 2012).');
            $table->boolean('accepts_information_received')->default(false)->comment('Declaración de información recibida de COODESCOR.');
            $table->boolean('accepts_funds_origin')->default(false)->comment('Declaración de origen de fondos.');

            // Revisión y aprobación.
            $table->foreignId('submitted_by')->nullable()->comment('Usuario que envió la versión a verificación.')->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable()->comment('Fecha de envío a verificación.');
            $table->foreignId('verified_by')->nullable()->comment('Responsable de la verificación de la información.')->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable()->comment('Fecha de la verificación.');
            $table->text('verification_observations')->nullable()->comment('Datos relevantes de la verificación.');
            $table->foreignId('compliance_by')->nullable()->comment('Oficial de cumplimiento que revisó al proveedor PEP.')->constrained('users')->nullOnDelete();
            $table->timestamp('compliance_at')->nullable()->comment('Fecha de la revisión del oficial de cumplimiento.');
            $table->text('compliance_observations')->nullable()->comment('Observaciones del oficial de cumplimiento.');
            $table->foreignId('rejected_by')->nullable()->comment('Usuario que rechazó la versión.')->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable()->comment('Fecha del rechazo.');
            $table->text('rejection_reason')->nullable()->comment('Motivo del rechazo.');
            $table->timestamp('approved_at')->nullable()->comment('Fecha en que quedó aprobada por todos los procesos vinculados.');

            $table->auditColumns();
            $table->unique(['provider_id', 'number']);
        });

        Schema::table('providers', function (Blueprint $table): void {
            $table->foreign('current_version_id')->references('id')->on('provider_versions')->nullOnDelete();
        });

        Schema::create('provider_version_contacts', function (Blueprint $table): void {
            $table->comment('Contactos del proveedor por cargo fijo del formato FO-300-01 (parte inmutable de la versión).');
            $table->id()->comment('Identificador del contacto.');
            $table->foreignId('provider_version_id')->comment('Versión del formulario.')->constrained('provider_versions')->cascadeOnDelete();
            $table->string('role', 30)->comment('Cargo: sales_manager, sales_representative, billing, collections o technical_director.');
            $table->string('name', 150)->nullable()->comment('Nombre del funcionario.');
            $table->string('phone', 50)->nullable()->comment('Teléfono o celular.');
            $table->string('email', 150)->nullable()->comment('Correo electrónico.');
            $table->timestamp('created_at')->nullable()->comment('Fecha de creación del registro.');
            $table->timestamp('updated_at')->nullable()->comment('Fecha de la última modificación.');
            $table->unique(['provider_version_id', 'role']);
        });

        Schema::create('provider_version_shareholders', function (Blueprint $table): void {
            $table->comment('Propietarios, consorciados o accionistas con participación de 5 % o más (persona jurídica).');
            $table->id()->comment('Identificador del accionista.');
            $table->foreignId('provider_version_id')->comment('Versión del formulario.')->constrained('provider_versions')->cascadeOnDelete();
            $table->string('document_type', 5)->comment('Tipo de documento del accionista.');
            $table->string('identification', 20)->comment('Número de identificación del accionista.');
            $table->string('name', 255)->comment('Nombre completo o razón social.');
            $table->string('legal_representative_name', 255)->nullable()->comment('Representante legal, si el accionista es persona jurídica.');
            $table->string('legal_representative_identification', 20)->nullable()->comment('Identificación del representante legal del accionista.');
            $table->decimal('ownership_percentage', 5, 2)->comment('Porcentaje de participación.');
            $table->timestamp('created_at')->nullable()->comment('Fecha de creación del registro.');
            $table->timestamp('updated_at')->nullable()->comment('Fecha de la última modificación.');
        });

        Schema::create('provider_version_pep_relatives', function (Blueprint $table): void {
            $table->comment('Grupo familiar o personas relacionadas con un PEP declaradas en la versión.');
            $table->id()->comment('Identificador del registro.');
            $table->foreignId('provider_version_id')->comment('Versión del formulario.')->constrained('provider_versions')->cascadeOnDelete();
            $table->string('document_type', 5)->nullable()->comment('Tipo de documento del familiar o relacionado.');
            $table->string('identification', 20)->nullable()->comment('Número de documento.');
            $table->string('name', 255)->comment('Nombres y apellidos.');
            $table->string('relationship', 100)->comment('Parentesco o relación, desde Características.');
            $table->boolean('is_pep')->default(false)->comment('La persona relacionada es PEP.');
            $table->string('public_office', 150)->nullable()->comment('Cargo PEP de la persona relacionada.');
            $table->date('pep_starts_on')->nullable()->comment('Inicio en el cargo PEP.');
            $table->date('pep_ends_on')->nullable()->comment('Fin del cargo PEP; vacía si sigue vigente.');
            $table->timestamp('created_at')->nullable()->comment('Fecha de creación del registro.');
            $table->timestamp('updated_at')->nullable()->comment('Fecha de la última modificación.');
        });

        Schema::create('provider_version_documents', function (Blueprint $table): void {
            $table->comment('Anexos de la versión (identificación, cámara de comercio, RUT, políticas de devolución, formulario firmado).');
            $table->id()->comment('Identificador del anexo.');
            $table->foreignId('provider_version_id')->comment('Versión del formulario.')->constrained('provider_versions')->cascadeOnDelete();
            $table->string('type', 30)->comment('Tipo de anexo (ProviderDocumentType).');
            $table->string('path', 255)->comment('Ruta del archivo en el disco privado local.');
            $table->string('original_name', 255)->comment('Nombre original del archivo cargado.');
            $table->date('issued_on')->nullable()->comment('Fecha de expedición (obligatoria para la cámara de comercio).');
            $table->foreignId('uploaded_by')->nullable()->comment('Usuario que cargó el anexo.')->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable()->comment('Fecha de carga.');
            $table->timestamp('updated_at')->nullable()->comment('Fecha de la última modificación.');
        });

        Schema::create('provider_linkages', function (Blueprint $table): void {
            $table->comment('Vinculación de un proveedor con un proceso (administrativo o comercial); cada proceso la gestiona por separado.');
            $table->id()->comment('Identificador de la vinculación.');
            $table->foreignId('provider_id')->comment('Proveedor vinculado.')->constrained('providers')->cascadeOnDelete();
            $table->string('scope', 20)->comment('Tipo de proveedor: administrative o commercial.');
            $table->string('status', 20)->index()->comment('Estado: pending, active, inactive o rejected.');
            $table->string('commercial_segment', 100)->nullable()->comment('Segmento comercial, desde Características (si aplica).');
            $table->string('payment_condition', 100)->nullable()->comment('Condición de pago negociada por el proceso, desde Características.');
            $table->string('payment_term', 100)->nullable()->comment('Plazo de pago negociado por el proceso, desde Características.');
            $table->foreignId('approved_by')->nullable()->comment('Usuario que aprobó la vinculación por primera vez.')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->comment('Fecha de la primera aprobación.');
            $table->auditColumns();
            $table->unique(['provider_id', 'scope']);
        });

        Schema::create('provider_version_approvals', function (Blueprint $table): void {
            $table->comment('Firmas de aprobación de una versión por proceso; si es PEP aprueba Gerencia por todos.');
            $table->id()->comment('Identificador de la aprobación.');
            $table->foreignId('provider_version_id')->comment('Versión aprobada.')->constrained('provider_versions')->cascadeOnDelete();
            $table->string('scope', 20)->comment('Proceso cuya vinculación se aprueba: administrative o commercial.');
            $table->foreignId('user_id')->nullable()->comment('Usuario que aprobó (director del proceso, su reemplazo o Gerencia).')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->comment('Fecha de la aprobación.');
            $table->text('observations')->nullable()->comment('Observaciones del aprobador.');
            $table->timestamp('created_at')->nullable()->comment('Fecha de creación del registro.');
            $table->timestamp('updated_at')->nullable()->comment('Fecha de la última modificación.');
            $table->unique(['provider_version_id', 'scope']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_version_approvals');
        Schema::dropIfExists('provider_linkages');
        Schema::dropIfExists('provider_version_documents');
        Schema::dropIfExists('provider_version_pep_relatives');
        Schema::dropIfExists('provider_version_shareholders');
        Schema::dropIfExists('provider_version_contacts');

        Schema::table('providers', function (Blueprint $table): void {
            $table->dropForeign(['current_version_id']);
        });

        Schema::dropIfExists('provider_versions');
        Schema::dropIfExists('providers');
    }
};
