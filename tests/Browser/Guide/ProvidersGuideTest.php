<?php

/*
 * Capturas de la guía de usuario: sección Proveedores (resources/guide/providers/*.md).
 *
 * Cada prueba siembra datos ficticios, recorre la pantalla como lo haría el usuario y guarda
 * las imágenes con guideShot(). Si una pantalla cambia y un selector deja de existir, la prueba
 * falla: es la señal para revisar el tema de la guía correspondiente.
 */

use App\Enums\IdentityDocumentType;
use App\Enums\PersonType;
use App\Enums\ProviderContactRole;
use App\Enums\ProviderDocumentType;
use App\Enums\ProviderLinkageStatus;
use App\Enums\ProviderRequestType;
use App\Enums\ProviderScope;
use App\Enums\ProviderVersionStatus;
use App\Enums\RiskLevel;
use App\Models\EconomicActivity;
use App\Models\GeographicLocation;
use App\Models\Process;
use App\Models\Provider;
use App\Models\ProviderLegacyUpdate;
use App\Models\ProviderLinkage;
use App\Models\ProviderVersion;
use App\Models\User;
use App\Settings\CompanySettings;
use Database\Seeders\CharacteristicOptionSeeder;
use Database\Seeders\ProcessSeeder;
use Illuminate\Support\Str;

/**
 * Catálogos mínimos para los formularios: dos departamentos, sus municipios, Colombia y dos CIIU.
 *
 * @return array{department: GeographicLocation, municipality: GeographicLocation, country: GeographicLocation, activity: EconomicActivity}
 */
function providersGuideCatalogs(): array
{
    test()->seed([ProcessSeeder::class, CharacteristicOptionSeeder::class]);

    $colombia = GeographicLocation::factory()->create(['code' => 'CO', 'name' => 'Colombia']);
    $department = GeographicLocation::factory()->department($colombia)->create(['code' => '23', 'name' => 'Córdoba']);
    $municipality = GeographicLocation::factory()->city($department)->create(['code' => '23001', 'name' => 'Montería']);
    GeographicLocation::factory()->city($department)->create(['code' => '23162', 'name' => 'Cereté']);
    $antioquia = GeographicLocation::factory()->department($colombia)->create(['code' => '05', 'name' => 'Antioquia']);
    GeographicLocation::factory()->city($antioquia)->create(['code' => '05001', 'name' => 'Medellín']);

    $activity = EconomicActivity::create(['code' => '4761', 'description' => 'Comercio al por menor de libros, periódicos, y materiales y artículos de papelería y escritorio']);
    EconomicActivity::create(['code' => '8121', 'description' => 'Limpieza general interior de edificios']);

    return [
        'department' => $department,
        'municipality' => $municipality,
        'country' => $colombia,
        'activity' => $activity,
    ];
}

/**
 * Proveedor con su versión del FO-300-01 en el estado indicado y su vinculación al proceso.
 *
 * @param  array<string, mixed>  $provider  Atributos del tercero (nombre, identificación...).
 * @param  array<string, mixed>  $version  Atributos adicionales de la versión.
 */
function providersGuideWithForm(array $catalogs, array $provider, ProviderVersionStatus $status, ProviderScope $scope = ProviderScope::Administrative, array $version = []): ProviderLinkage
{
    $model = Provider::factory()->create([
        'email' => 'contacto@'.Str::slug($provider['name'], '').'.test',
        'phone' => '6047891234',
        'address' => 'Calle 27 # 4-15',
        'municipality_id' => $catalogs['municipality']->id,
        ...$provider,
    ]);

    $factory = ProviderVersion::factory()->status($status);
    $factory = $status === ProviderVersionStatus::Approved ? $factory->approved() : $factory;
    $factory->create([
        'provider_id' => $model->id,
        'email' => $model->email,
        'phone' => $model->phone,
        'address' => $model->address,
        'department_id' => $catalogs['department']->id,
        'municipality_id' => $catalogs['municipality']->id,
        'country_id' => $catalogs['country']->id,
        'economic_activity_id' => $catalogs['activity']->id,
        'economic_activity_description' => 'Venta de papelería y útiles de oficina',
        'monthly_income' => 45000000,
        'submitted_at' => $status === ProviderVersionStatus::Draft ? null : now()->subDays(2),
        ...($model->person_type === PersonType::Legal ? [
            'tax_regime' => 'Responsable de IVA',
            'company_type' => 'private',
            'legal_representative_name' => 'CAMILO ANDRÉS TORRES VEGA',
            'legal_representative_document_type' => IdentityDocumentType::CitizenshipCard,
            'legal_representative_identification' => '10777888',
            'total_assets' => 820000000,
            'total_liabilities' => 310000000,
            'total_equity' => 510000000,
        ] : []),
        ...$version,
    ]);

    return ProviderLinkage::factory()->scope($scope)->create([
        'provider_id' => $model->id,
        'status' => $status === ProviderVersionStatus::Approved ? ProviderLinkageStatus::Active : ProviderLinkageStatus::Pending,
        'approved_at' => $status === ProviderVersionStatus::Approved ? now() : null,
        'payment_condition' => 'Crédito',
        'payment_term' => '30 días',
    ]);
}

/** Proveedor básico (sin FO-300-01) con vinculación administrativa activa. */
function providersGuideBasic(array $catalogs, array $attributes): ProviderLinkage
{
    $provider = Provider::factory()->create([
        'risk_level' => null,
        'email' => 'ventas@'.Str::slug($attributes['name'], '').'.test',
        'phone' => '3104567890',
        'address' => 'Carrera 3 # 30-12',
        'municipality_id' => $catalogs['municipality']->id,
        'contact_name' => 'LAURA MÁRQUEZ',
        ...$attributes,
    ]);

    return ProviderLinkage::factory()->scope(ProviderScope::Administrative)->active()->create([
        'provider_id' => $provider->id,
        'payment_condition' => 'Contado',
        'payment_term' => 'Contado',
    ]);
}

/**
 * Listado administrativo de ejemplo: un proveedor por cada situación del formato.
 *
 * @return array<string, ProviderLinkage>
 */
function providersGuideList(array $catalogs): array
{
    $lastYearEnd = now()->subYear()->endOfYear()->startOfDay();
    $rows = [
        'basic' => providersGuideBasic($catalogs, ['identification' => '901234567', 'name' => 'PAPELERÍA EL SINÚ S.A.S.']),
        // Formatos del año anterior: la regla anual permite abrir la actualización de este año.
        'current' => providersGuideWithForm($catalogs, ['identification' => '900456789', 'name' => 'CAFETERÍA Y SUMINISTROS LA RIBERA S.A.S.'], ProviderVersionStatus::Approved,
            version: ['created_at' => $lastYearEnd->subDays(25), 'approved_at' => $lastYearEnd->subDays(20)]),
        'draft' => providersGuideWithForm($catalogs, [
            'identification' => '1067888999', 'name' => 'JUAN CARLOS ÁLVAREZ MESA', 'person_type' => PersonType::Natural,
            'document_type' => IdentityDocumentType::CitizenshipCard, 'risk_level' => RiskLevel::Medium,
        ], ProviderVersionStatus::Draft),
        'expired' => providersGuideWithForm($catalogs, ['identification' => '900987654', 'name' => 'ASEO INTEGRAL DEL CARIBE S.A.S.'], ProviderVersionStatus::Approved,
            version: ['created_at' => now()->subYear()->subDays(30), 'approved_at' => now()->subYear()->subDays(20)]),
        'pep' => providersGuideWithForm($catalogs, ['identification' => '900321654', 'name' => 'SUMINISTROS DE OFICINA LA 41 S.A.S.', 'risk_level' => RiskLevel::High, 'is_pep' => true],
            ProviderVersionStatus::Verification, version: ['declares_pep' => true, 'public_office' => 'Concejal municipal', 'pep_starts_on' => '2024-01-01', 'is_pep' => true, 'risk_level' => RiskLevel::High]),
        'inactive' => providersGuideBasic($catalogs, ['identification' => '1065432100', 'name' => 'MIGUEL ÁNGEL PÉREZ ROMERO', 'person_type' => PersonType::Natural,
            'document_type' => IdentityDocumentType::CitizenshipCard]),
    ];

    // Vigente: vence un año después de su aprobación. Vencido: aprobado hace más de un año.
    $rows['current']->provider->update(['next_update_on' => $lastYearEnd->subDays(20)->addYear()->toDateString()]);
    $rows['expired']->provider->update(['next_update_on' => now()->subDays(20)->toDateString()]);
    $rows['inactive']->update(['status' => ProviderLinkageStatus::Inactive]);

    return $rows;
}

it('genera las capturas de los conceptos de proveedores', function (): void {
    $catalogs = providersGuideCatalogs();
    providersGuideList($catalogs);
    $this->actingAs(providersGuideAdmin());

    $page = visit('/administrative/providers')->resize(1440, 900);
    $page->assertSee('PAPELERÍA EL SINÚ S.A.S.');
    // Espera a que termine la animación de carga de la página.
    $page->wait(1.5);

    guideShot($page, 'providers-overview/01-administrative-list', [
        ['table thead th:nth-child(5)', '1'],
        ['table thead th:nth-child(6)', '2'],
        ['table thead th:nth-child(7)', '3'],
        ['table thead th:nth-child(9)', '4'],
    ], '.card');
});

/** Administrador con nombre ficticio legible (aparece en firmas, anexos y la barra superior). */
function providersGuideAdmin(): User
{
    $admin = actingAsAdmin();
    $admin->update(['name' => 'LUIS FERNANDO', 'lastname' => 'GÓMEZ ARIAS']);

    return $admin;
}

/** Abre la pantalla con el tamaño indicado (los modales grandes se capturan con más alto). */
function providersGuideVisit(string $url, int $width = 1440, int $height = 900): object
{
    return visit($url)->resize($width, $height);
}

/** Ejecuta un clic por selector CSS desde el navegador (botones de solo ícono). */
function providersGuideClick(object $page, string $selector, float $wait = 1): void
{
    $page->script('document.querySelector('.json_encode($selector).').click()');
    $page->wait($wait);
}

/** Desplaza el cuerpo del modal abierto hasta el elemento indicado. */
function providersGuideScrollTo(object $page, string $selector): void
{
    $page->script('document.querySelector('.json_encode($selector).').scrollIntoView({ block: "start" })');
    $page->wait(0.5);
}

it('genera las capturas del proveedor básico', function (): void {
    $catalogs = providersGuideCatalogs();
    $rows = providersGuideList($catalogs);
    // Proveedor que solo existe en Compras: al digitar su identificación se ofrece vincularlo.
    providersGuideWithForm($catalogs, ['identification' => '890111222', 'name' => 'DISTRIBUIDORA FARMACÉUTICA DEL NORTE S.A.S.'],
        ProviderVersionStatus::Approved, ProviderScope::Commercial);
    $this->actingAs(providersGuideAdmin());

    // 1. Botón Agregar del listado.
    $page = providersGuideVisit('/administrative/providers');
    $page->assertSee('PAPELERÍA EL SINÚ S.A.S.');
    guideShot($page, 'providers-basic/01-add-button', [['button[aria-label="Agregar"]', '1']]);

    // 2. Modal vacío: identificación primero y acceso al registro con FO-300-01.
    $page = providersGuideVisit('/administrative/providers', 1440, 1500);
    providersGuideClick($page, 'button[aria-label="Agregar"]');
    $page->assertSee('Registrar proveedor');
    guideShot($page, 'providers-basic/02-empty-modal', [
        ['#basic-person-type', '1'],
        ['#basic-document-type', '2'],
        ['#basic-identification', '3'],
        ['.app-modal.show .alert-info button', '4'],
    ], '.app-modal.show .modal-content');

    // 3. Identificación ya registrada en otro proceso.
    $page->type('#basic-identification', '890111222');
    $page->keys('#basic-identification', ['Tab']);
    $page->wait(1)->assertSee('DISTRIBUIDORA FARMACÉUTICA DEL NORTE S.A.S.');
    guideShot($page, 'providers-basic/03-existing-provider', [
        ['.app-modal.show .alert-warning', '1'],
        ['.app-modal.show .alert-warning button', '2'],
    ], '.app-modal.show .modal-content');

    // 4. Formulario diligenciado.
    $page = providersGuideVisit('/administrative/providers', 1440, 1500);
    providersGuideClick($page, 'button[aria-label="Agregar"]');
    $page->type('#basic-identification', '901777333');
    $page->keys('#basic-identification', ['Tab']);
    $page->type('#basic-name', 'TINTAS Y PAPELES DEL SINÚ S.A.S.');
    $page->type('#basic-contact', 'ANDREA LÓPEZ');
    $page->select('#basic-department', (string) $catalogs['department']->id);
    $page->wait(1);
    $page->select('#basic-municipality', (string) $catalogs['municipality']->id);
    $page->type('#basic-address', 'Calle 31 # 2-45, local 3');
    $page->type('#basic-phone', '3157778899');
    $page->type('#basic-email', 'ventas@tintasdelsinu.test');
    $page->select('#basic-payment-condition', 'Crédito');
    $page->select('#basic-payment-term', '30 días');
    $page->wait(0.5);
    guideShot($page, 'providers-basic/04-filled-form', [
        ['#basic-name', '1'],
        ['#basic-department', '2'],
        ['#basic-payment-condition', '3'],
        ['.app-modal.show .app-modal-submit', '4'],
    ], '.app-modal.show .modal-content');

    // 5. Editar: seleccionar la fila y usar el lápiz.
    $checkbox = 'tr[wire\\:key="provider-linkage-'.$rows['basic']->id.'"] input[type="checkbox"]';
    $page = providersGuideVisit('/administrative/providers');
    providersGuideClick($page, $checkbox);
    guideShot($page, 'providers-basic/05-edit-selected', [
        [$checkbox, '1'],
        ['button[aria-label="Editar seleccionado"]', '2'],
        ['button[aria-label="Ver detalle"]', '3'],
    ], '.card');

    // 6. Detalle del proveedor básico.
    $page = providersGuideVisit('/administrative/providers/'.$rows['basic']->id, 1440, 1000);
    $page->assertSee('Diligenciar FO-300-01');
    guideShot($page, 'providers-basic/06-basic-detail', [
        ['button[wire\\:click="openTerms"]', '1'],
        ['.document-header button[wire\\:click^="$dispatch"]', '2'],
        ['.document-header button.btn-primary', '3'],
        ['.alert-info', '4'],
    ]);

    // 7. Confirmación al diligenciar el FO-300-01.
    providersGuideClick($page, '.document-header button.btn-primary');
    $page->assertSee('Se abrirá el FO-300-01 en Borrador');
    guideShot($page, 'providers-basic/07-start-form-confirm', [], '.swal2-popup');

    // 8. Proveedor básico con formatos de años anteriores (cargados del sistema anterior): sigue sin FO-300-01.
    $historical = providersGuideBasic($catalogs, ['identification' => '900135792', 'name' => 'PAPELES Y CARTONES DEL CARIBE S.A.S.']);
    ProviderVersion::factory()->status(ProviderVersionStatus::Approved)->create([
        'provider_id' => $historical->provider_id, 'created_at' => now()->subYear()->startOfYear()->addMonths(2),
        'approved_at' => now()->subYear()->startOfYear()->addMonths(2)->addDays(5), 'municipality_id' => $catalogs['municipality']->id,
        'risk_level' => RiskLevel::Low, 'email' => $historical->provider->email, 'phone' => $historical->provider->phone,
        'address' => $historical->provider->address,
    ]);
    $page = providersGuideVisit('/administrative/providers/'.$historical->id, 1440, 1000);
    providersGuideClick($page, '.document-header button:has(.bi-calendar3)', 0.8);
    $page->assertSee('Tiene formatos de años anteriores');
    guideShot($page, 'providers-basic/08-historical-years', [
        ['.document-header button[wire\:click^="$dispatch"]', '1'],
        ['.document-header button.btn-primary', '2'],
        ['.document-header button:has(.bi-calendar3)', '3'],
        ['.provider-years-card', '4'],
        ['.alert-info:has(.bi-info-circle)', '5'],
    ]);
});

it('genera las capturas del diligenciamiento del FO-300-01', function (): void {
    $catalogs = providersGuideCatalogs();
    $admin = providersGuideAdmin();
    $linkage = providersGuideWithForm($catalogs, ['identification' => '901555666', 'name' => 'ASEO Y DOTACIONES DEL SINÚ S.A.S.'],
        ProviderVersionStatus::Draft, version: ['accepts_risk_bureau' => true]);
    $version = $linkage->provider->versions()->firstOrFail();
    $version->contacts()->create(['role' => ProviderContactRole::SalesRepresentative, 'name' => 'DIANA PATRICIA OSORIO', 'phone' => '3008765432', 'email' => 'dosorio@aseosinu.test']);
    $version->shareholders()->create(['document_type' => IdentityDocumentType::CitizenshipCard, 'identification' => '10777888',
        'name' => 'CAMILO ANDRÉS TORRES VEGA', 'ownership_percentage' => 60]);
    $version->documents()->create(['type' => ProviderDocumentType::Rut, 'path' => 'providers/guide/rut.pdf',
        'original_name' => 'RUT ASEO Y DOTACIONES.pdf', 'uploaded_by' => $admin->id]);
    $this->actingAs($admin);
    $url = '/administrative/providers/'.$linkage->id;

    // 1. Detalle del Borrador: Editar borrador.
    $page = providersGuideVisit($url);
    $page->assertSee('Editar borrador');
    guideShot($page, 'providers-form/01-draft-detail', [
        ['button[wire\\:click="editDraft"]', '1'],
        ['.card-header .badge.bg-secondary', '2'],
    ]);

    // 2. Modal del formato: primeras secciones.
    $page = providersGuideVisit($url, 1440, 1500);
    providersGuideClick($page, 'button[wire\\:click="editDraft"]', 1.5);
    $page->assertSee('Editar proveedor (FO-300-01)');
    guideShot($page, 'providers-form/02-form-top', [
        ['#provider-person-type', '1'],
        ['#provider-identification', '2'],
        ['#provider-department', '3'],
    ], '.app-modal.show .modal-content');

    // 3. Actividad económica, información financiera y PEP.
    providersGuideScrollTo($page, '#form-economic_activity_id');
    guideShot($page, 'providers-form/03-form-financial-pep', [
        ['#form-economic_activity_id', '1'],
        ['#provider-monthly-income', '2'],
        ['#provider-public-resources', '3'],
        ['#provider-declares-pep', '3'],
        ['#provider-pep-relationship', '3'],
    ], '.app-modal.show .modal-content');

    // 4. Contactos y declaraciones obligatorias.
    providersGuideScrollTo($page, '#provider-contact-sales_manager-name');
    guideShot($page, 'providers-form/04-form-contacts-declarations', [
        ['#provider-contact-sales_representative-name', '1'],
        ['#provider-truthfulness', '2'],
        ['#provider-data-processing', '2'],
        ['#provider-information-received', '2'],
        ['#provider-funds-origin', '2'],
        ['.app-modal.show .app-modal-submit', '3'],
    ], '.app-modal.show .modal-content');

    // 5. Accionistas, familiares PEP y anexos en el detalle.
    $page = providersGuideVisit($url, 1440, 1000);
    guideShot($page, 'providers-form/05-shareholders', [
        ['#shareholder-pct', '1'],
        ['form[wire\\:submit="addShareholder"] button[type="submit"]', '2'],
    ], '.document-lines:has(#shareholder-pct)');

    $page->script("document.querySelector('#relative-is-pep').click()");
    $page->wait(1);
    guideShot($page, 'providers-form/06-pep-relatives', [
        ['#relative-relationship', '1'],
        ['#relative-is-pep', '2'],
        ['#relative-office', '3'],
    ], '.document-lines:has(#relative-name)');

    $page->select('#document-type', ProviderDocumentType::ChamberOfCommerce->value);
    $page->wait(1);
    guideShot($page, 'providers-form/07-documents', [
        ['#document-type', '1'],
        ['#document-issued', '2'],
        ['#document-file', '3'],
        ['form[wire\\:submit="addDocument"] button[type="submit"]', '4'],
    ], '.document-lines:has(#document-file)');

    // 6. Enviar a verificación.
    guideShot($page, 'providers-form/08-submit', [
        ['.card:has(.bi-pen) .list-group', '1'],
        ['.card:has(.bi-pen) button.btn-primary', '2'],
    ], '.card:has(.bi-pen)');
});

it('genera las capturas de la verificación y aprobación', function (): void {
    $catalogs = providersGuideCatalogs();
    $admin = providersGuideAdmin();
    $other = User::factory()->create(['name' => 'SANDRA MILENA', 'lastname' => 'RÍOS CANO']);

    $inVerification = providersGuideWithForm($catalogs, ['identification' => '900222333', 'name' => 'PAPELERÍA Y TINTAS DEL CARIBE S.A.S.'],
        ProviderVersionStatus::Verification, version: ['submitted_by' => $other->id]);

    $inCompliance = providersGuideWithForm($catalogs, ['identification' => '900321654', 'name' => 'SUMINISTROS DE OFICINA LA 41 S.A.S.', 'risk_level' => RiskLevel::High, 'is_pep' => true],
        ProviderVersionStatus::Compliance, version: ['declares_pep' => true, 'public_office' => 'Concejal municipal', 'pep_starts_on' => '2024-01-01',
            'is_pep' => true, 'risk_level' => RiskLevel::High, 'submitted_by' => $other->id, 'verified_by' => $other->id, 'verified_at' => now()->subDay()]);

    $inApproval = providersGuideWithForm($catalogs, ['identification' => '900444555', 'name' => 'COMERCIALIZADORA DE INSUMOS MONTERÍA S.A.S.'],
        ProviderVersionStatus::Approval, version: ['submitted_by' => $other->id, 'verified_by' => $other->id, 'verified_at' => now()->subDay(),
            'verification_observations' => 'RUT y cámara de comercio verificados.']);
    ProviderLinkage::factory()->scope(ProviderScope::Commercial)->create(['provider_id' => $inApproval->provider_id]);

    $rejected = providersGuideWithForm($catalogs, ['identification' => '900888777', 'name' => 'IMPRESOS LA PLAZA S.A.S.'],
        ProviderVersionStatus::Rejected, version: ['submitted_by' => $other->id, 'rejected_by' => $other->id, 'rejected_at' => now()->subDay(),
            'rejection_reason' => 'El certificado de cámara de comercio tiene más de tres meses.']);
    $rejected->update(['status' => ProviderLinkageStatus::Rejected]);

    // El administrador es director de Administrativo y oficial de cumplimiento en estas capturas.
    Process::where('name', 'Administrativo')->update(['director_id' => $admin->id]);
    $settings = app(CompanySettings::class);
    $settings->compliance_officer_id = $admin->id;
    $settings->save();
    $this->actingAs($admin);

    // 1. Verificación.
    $page = providersGuideVisit('/administrative/providers/'.$inVerification->id, 1440, 1000);
    $page->assertSee('Verificar');
    guideShot($page, 'providers-review/01-verify', [
        ['#review-notes', '1'],
        ['button[wire\\:click="verify"]', '2'],
        ['.card:has(.bi-pen) button.btn-outline-danger', '3'],
        ['.card:has(.bi-pen) button.btn-outline-secondary', '4'],
    ], '.card:has(.bi-pen)');

    // 2. Oficial de cumplimiento (PEP).
    $page = providersGuideVisit('/administrative/providers/'.$inCompliance->id, 1440, 1000);
    $page->assertSee('Visto bueno de cumplimiento');
    guideShot($page, 'providers-review/02-compliance', [
        ['.card:has(.bi-pen) .list-group-item:nth-child(3)', '1'],
        ['button[wire\\:click="certifyCompliance"]', '2'],
    ], '.card:has(.bi-pen)');

    // 3. Aprobación por proceso.
    $page = providersGuideVisit('/administrative/providers/'.$inApproval->id, 1440, 1000);
    $page->assertSee('pendiente (director de Comercial)');
    guideShot($page, 'providers-review/03-approve', [
        ['.card:has(.bi-pen) .list-group-item:nth-child(3)', '1'],
        ['.card:has(.bi-pen) .list-group-item:nth-child(4)', '2'],
        ['button[wire\\:click="approve"]', '3'],
    ], '.card:has(.bi-pen)');

    // 4. Rechazo: confirmación y resultado.
    $page = providersGuideVisit('/administrative/providers/'.$inVerification->id, 1440, 1000);
    $page->type('#review-notes', 'El RUT anexo no corresponde al proveedor.');
    providersGuideClick($page, '.card:has(.bi-pen) button.btn-outline-danger');
    $page->assertSee('Las vinculaciones pendientes quedarán rechazadas.');
    guideShot($page, 'providers-review/04-reject-confirm', [], '.swal2-popup');

    $page = providersGuideVisit('/administrative/providers/'.$rejected->id, 1440, 1000);
    $page->assertSee('Corregir FO-300-01');
    guideShot($page, 'providers-review/05-rejected', [
        ['.document-header .badge.bg-danger', '1'],
        ['.card-body > .alert-danger', '2'],
        ['.document-header button:has(.bi-pencil)', '3'],
    ]);

    // 5. Corregir el rechazado: la misma versión vuelve a Borrador con el motivo a la vista.
    providersGuideClick($page, '.document-header button:has(.bi-pencil)');
    providersGuideClick($page, '.swal2-confirm', 1.5);
    $page->assertSee('Corrección de un rechazo');
    guideShot($page, 'providers-review/06-correction', [
        ['.card-header .badge.bg-secondary', '1'],
        ['.card-body > .alert-warning', '2'],
        ['.document-header .badge', '3'],
    ]);

    // 6. Otro proceso se vincula en el mismo año del formato aprobado: firma sobre la versión vigente.
    $sameYear = providersGuideWithForm($catalogs, ['identification' => '900777444', 'name' => 'DOTACIONES HOSPITALARIAS DEL SINÚ S.A.S.'],
        ProviderVersionStatus::Approved, version: ['submitted_by' => $other->id, 'verified_by' => $other->id, 'verified_at' => now()->subDay()]);
    $commercial = ProviderLinkage::factory()->scope(ProviderScope::Commercial)->create(['provider_id' => $sameYear->provider_id]);
    Process::where('name', 'Comercial')->update(['director_id' => $admin->id]);

    $page = providersGuideVisit('/shopping/providers/'.$commercial->id, 1440, 1000);
    $page->assertSee('pendiente (director de Comercial)');
    guideShot($page, 'providers-review/07-same-year-linkage', [
        ['.card:has(.bi-pen) .card-body .list-group-item.text-body-secondary', '1'],
        ['button[wire\\:click="approve"]', '2'],
    ], '.card:has(.bi-pen)');

    // 7. PDF y años del formato: dos versiones y una actualización del sistema anterior.
    $provider = Provider::factory()->create(['identification' => '900666111', 'name' => 'ÚTILES Y OFICINA DEL CARIBE S.A.S.', 'municipality_id' => $catalogs['municipality']->id,
        'email' => 'ventas@utilesdelcaribe.test', 'phone' => '6047812233', 'address' => 'Avenida Primera # 40-10']);
    ProviderLegacyUpdate::create(['provider_id' => $provider->id, 'year' => now()->year - 2, 'updated_on' => now()->subYears(2)->subMonth()->toDateString()]);
    ProviderVersion::factory()->approved()->create(['provider_id' => $provider->id, 'created_at' => now()->subYear()->subDays(10),
        'municipality_id' => $catalogs['municipality']->id, 'address' => $provider->address, 'email' => $provider->email])->update(['approved_at' => now()->subYear()]);
    ProviderVersion::factory()->approved()->create(['provider_id' => $provider->id, 'number' => 2, 'request_type' => ProviderRequestType::Update,
        'municipality_id' => $catalogs['municipality']->id, 'address' => $provider->address, 'email' => $provider->email]);
    $history = ProviderLinkage::factory()->scope(ProviderScope::Administrative)->active()->create(['provider_id' => $provider->id, 'payment_condition' => 'Crédito', 'payment_term' => '30 días']);

    $page = providersGuideVisit('/administrative/providers/'.$history->id, 1440, 1000);
    guideShot($page, 'providers-review/08-pdf-button', [
        ['.document-header button[wire\\:click^="downloadPdf"]', '1'],
        ['.document-header button:has(.bi-calendar3)', '2'],
    ]);

    providersGuideClick($page, '.document-header button:has(.bi-calendar3)', 0.8);
    $page->assertSee('Años del FO-300-01');
    guideShot($page, 'providers-review/09-years', [
        ['.provider-year.active', '1'],
        ['button.provider-year:not(.active)', '2'],
        ['.provider-year-legacy', '3'],
    ], '.provider-years-card');

    providersGuideClick($page, 'button.provider-year:not(.active)', 1.5);
    $page->assertSee('solo lectura');
    guideShot($page, 'providers-review/10-older-year', [
        ['.provider-year.active', '1'],
        ['.alert-info:has(.bi-eye)', '2'],
        ['.alert-info:has(.bi-eye) button', '3'],
        ['.document-header button[wire\\:click^="downloadPdf"]', '4'],
    ]);
});

it('genera las capturas de la actualización anual, vinculación y estado', function (): void {
    $catalogs = providersGuideCatalogs();
    $rows = providersGuideList($catalogs);
    $this->actingAs(providersGuideAdmin());

    // 1. Proveedor vigente: Actualizar FO-300-01.
    $page = providersGuideVisit('/administrative/providers/'.$rows['current']->id, 1440, 1000);
    $page->assertSee('Actualizar FO-300-01');
    guideShot($page, 'providers-maintenance/01-update-button', [
        ['.document-header .document-section:nth-child(3)', '1'],
        ['.document-header button.btn-primary', '2'],
    ]);

    providersGuideClick($page, '.document-header button.btn-primary');
    $page->assertSee('copiada de la vigente');
    guideShot($page, 'providers-maintenance/02-update-confirm', [], '.swal2-popup');

    // 2. Formato vencido.
    $page = providersGuideVisit('/administrative/providers/'.$rows['expired']->id, 1440, 1000);
    $page->assertSee('Vencida: no se pueden hacer órdenes');
    guideShot($page, 'providers-maintenance/03-expired', [
        ['.document-header .document-section:nth-child(3)', '1'],
    ], '.document-header');

    // 3. Vincular desde Compras un proveedor administrativo.
    $page = providersGuideVisit('/shopping/providers', 1440, 1500);
    providersGuideClick($page, 'button[aria-label="Agregar"]', 1.5);
    $page->type('#provider-identification', '900456789');
    $page->keys('#provider-identification', ['Tab']);
    $page->wait(1)->assertSee('Vincular a mi proceso');
    guideShot($page, 'providers-maintenance/04-link-other-process', [
        ['#provider-identification', '1'],
        ['.app-modal.show .alert-warning button', '2'],
    ], '.app-modal.show .modal-content');

    // 4. Activar o inactivar (pantalla ancha: el botón está a la vista).
    $checkbox = 'tr[wire\\:key="provider-linkage-'.$rows['inactive']->id.'"] input[type="checkbox"]';
    $page = providersGuideVisit('/administrative/providers', 1700, 900);
    providersGuideClick($page, $checkbox);
    guideShot($page, 'providers-maintenance/05-toggle', [
        [$checkbox, '1'],
        ['.table-actions-wide button[aria-label="Activar / inactivar seleccionados"]', '2'],
    ], '.card');

    // 5. Condiciones del proceso.
    $page = providersGuideVisit('/administrative/providers/'.$rows['current']->id, 1440, 1000);
    providersGuideClick($page, 'button[wire\\:click="openTerms"]');
    $page->assertSee('Condiciones del proceso');
    guideShot($page, 'providers-maintenance/06-terms', [
        ['#terms-condition', '1'],
        ['#terms-term', '2'],
    ], '.app-modal.show .modal-content');

});

it('genera la captura de los datos reservados', function (): void {
    $rows = providersGuideList(providersGuideCatalogs());

    // Quien solo consulta (sin "process") no ve la información financiera ni la PEP.
    $this->actingAs(userWith('administrative-providers-read'));
    $page = providersGuideVisit('/administrative/providers/'.$rows['current']->id, 1440, 1000);
    $page->assertSee('Datos reservados');
    guideShot($page, 'providers-maintenance/07-sensitive-hidden', [
        ['.document-section:has(.bi-lock)', '1'],
    ], '.card:not(.document-header):has(> .card-body > .document-sections)');
});
