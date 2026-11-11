<?php

/*
 * Capturas de la guía de usuario: secciones Primeros pasos (resources/guide/start/*.md)
 * y Administración (resources/guide/admin/*.md).
 *
 * Cada prueba siembra datos ficticios, recorre la pantalla como lo haría el usuario y guarda
 * las imágenes con guideShot(). Si una pantalla cambia y un selector deja de existir, la prueba
 * falla: es la señal para revisar el tema de la guía correspondiente.
 */

use App\Enums\ServicePointType;
use App\Enums\Status;
use App\Enums\UserStatus;
use App\Models\Characteristic;
use App\Models\GeographicLocation;
use App\Models\Process;
use App\Models\ProcessSubstitution;
use App\Models\Provider;
use App\Models\Role;
use App\Models\ServicePoint;
use App\Models\User;
use App\Settings\CompanySettings;
use Database\Seeders\CharacteristicOptionSeeder;
use Database\Seeders\ProcessSeeder;
use Database\Seeders\ProviderVerifierSeeder;
use Modules\HumanResources\Database\Seeders\PositionSeeder;
use Modules\Pharmacy\App\Models\StockOut;

/** Hace clic con JavaScript en el primer elemento del selector y espera la respuesta de Livewire. */
function startGuideClick(object $page, string $selector, float $seconds = 1): void
{
    $page->script('document.querySelector('.json_encode($selector).').click()');
    $page->wait($seconds);
}

/** Agrega margen interno a un elemento para que los recuadros numerados no queden cortados al recortar. */
function startGuidePad(object $page, string $selector, string $padding = '20px 10px 10px 20px'): void
{
    $page->script('document.querySelector('.json_encode($selector).').style.padding = '.json_encode($padding));
}

/**
 * Separa los botones unidos de la barra de acciones y agrega margen superior, para que cada
 * recuadro numerado quede sobre su botón sin tapar al vecino. Solo afecta la captura.
 */
function startGuideSpreadToolbar(object $page): void
{
    $page->wait(1);
    $page->script("document.querySelectorAll('.table-actions-group').forEach((group) => { group.style.display = 'flex'; group.style.gap = '16px'; });"
        ."document.querySelectorAll('.table-toolbar').forEach((bar) => bar.style.paddingTop = '18px');");
}

/**
 * Sedes, procesos y un administrador con nombre ficticio (Oficina Principal, proceso Administrativo).
 * Elimina el proceso y la sede de relleno que crea la fábrica de usuarios.
 */
function startGuideAdmin(): User
{
    test()->seed([ProcessSeeder::class]);

    $headquarters = ServicePoint::factory()->create([
        'code' => ServicePoint::HEADQUARTERS_CODE, 'name' => 'Oficina Principal', 'abbreviation' => 'OP',
        'type' => ServicePointType::Headquarters, 'address' => 'Calle 27 # 4-15', 'phone' => '6047891234',
    ]);

    $admin = actingAsAdmin();
    $fillerProcess = $admin->process_id;
    $fillerPoint = $admin->service_point_id;

    $admin->update([
        'identification' => '1067845210', 'name' => 'Laura', 'lastname' => 'Méndez Ríos',
        'email' => 'laura.mendez@empresa.test', 'service_point_id' => $headquarters->id,
        'process_id' => Process::named('Administrativo')?->id,
    ]);

    Process::whereKey($fillerProcess)->forceDelete();
    ServicePoint::whereKey($fillerPoint)->forceDelete();

    return $admin->refresh();
}

/** Usuario ficticio con proceso, sede y rol explícitos (sin datos de relleno de la fábrica). */
function startGuideUser(array $attributes): User
{
    return User::factory()->create([
        'process_id' => Process::named('Administrativo')?->id,
        'service_point_id' => ServicePoint::where('code', ServicePoint::HEADQUARTERS_CODE)->value('id'),
        ...$attributes,
    ]);
}

it('genera las capturas del ingreso', function (): void {
    startGuideUser([
        'name' => 'Andrés', 'lastname' => 'Pérez Gómez', 'email' => 'andres.perez@empresa.test',
        'status' => UserStatus::Inactive, 'role_id' => null,
    ]);

    $page = visit('/login')->resize(1440, 900);

    guideShot($page, 'getting-started/01-login', [
        ['#email', '1'], ['#password', '2'], ['.auth-card button[type="submit"]', '3'],
    ], '.auth-card');

    $page->type('#email', 'andres.perez@empresa.test')
        ->type('#password', 'password');
    $page->script("document.querySelector('.auth-card form').submit()");
    $page->wait(2);
    $page->assertSee('Tu cuenta está inactiva');

    guideShot($page, 'getting-started/02-inactive-user', [['.auth-card .alert-danger', '']], '.auth-card');
});

it('genera la captura del cambio de clave temporal', function (): void {
    startGuideAdmin();

    $user = startGuideUser(['name' => 'Camila', 'lastname' => 'Torres Ruiz', 'email' => 'camila.torres@empresa.test']);
    $user->forceFill(['must_change_password' => true])->save();

    $this->actingAs($user);

    $page = visit('/dashboard')->resize(1440, 900)->assertSee('Actualiza tu contraseña');

    guideShot($page, 'getting-started/03-force-password-change', [
        ['#password', '1'], ['#password_confirmation', '2'], ['form button[type="submit"]', '3'],
    ], '.card');
});

it('genera las capturas de navegación, perfil y guía', function (): void {
    $this->actingAs(startGuideAdmin());

    $page = visit('/processes')->resize(1440, 900);
    startGuideClick($page, 'a[href="#utilities-error"]', 0.6);
    $page->script("document.querySelector('.iq-navbar').style.paddingTop = '18px'");

    guideShot($page, 'getting-started/04-navigation', [
        ['a[href="#allPagesData"]', '1'],
        ['a[href="#sidebar-user"]', '2'],
        ['a[href="#utilities-error"]', '3'],
        ['a[href="#ui"]', '4'],
        ['#guide-help', '5'],
        ['#profile-setting', '6'],
    ]);

    startGuideClick($page, '#profile-setting', 0.6);

    guideShot($page, 'getting-started/05-profile-menu', [
        ['.dropdown-menu[aria-labelledby="profile-setting"]', ''],
    ]);

    $page = visit('/settings/profile')->resize(1440, 900);

    guideShot($page, 'getting-started/06-profile', [
        ['#photo', '1'], ['#name', '2'], ['#pending-service-point', '3'],
    ]);

    $page = visit('/guide?topic=getting-started')->resize(1440, 900);
    $page->type('.guide-nav input[type="search"]', 'clave')->wait(1);

    guideShot($page, 'getting-started/07-guide', [
        ['.guide-nav input[type="search"]', '1'], ['.guide-nav-list', '2'], ['.document-header', '3'],
    ]);
});

/** Hace clic con JavaScript en el primer elemento del selector cuyo texto contiene el indicado. */
function startGuideClickText(object $page, string $selector, string $text, float $seconds = 1): void
{
    $page->script('[...document.querySelectorAll('.json_encode($selector).')].find((el) => el.textContent.includes('.json_encode($text).')).click()');
    $page->wait($seconds);
}

/** Lanza un evento de Livewire (abre los modales igual que sus botones) y espera la respuesta. */
function startGuideDispatch(object $page, string $event, array $params = [], float $seconds = 1): void
{
    $page->script('Livewire.dispatch('.json_encode($event).', '.json_encode((object) $params).')');
    $page->wait($seconds);
}

/**
 * Directores ficticios de los procesos sembrados y procesos adicionales para llenar más de una página.
 * Deja inactivos Cartera y Tesorería y eliminado Jurídica, para mostrar los colores de las filas.
 *
 * @return array<string, User> Directores por nombre de proceso.
 */
function startGuideProcesses(bool $extra = true): array
{
    $directors = [];

    foreach ([
        'Gerencia' => ['Jorge', 'Restrepo Arias', 'jorge.restrepo@empresa.test'],
        'Administrativo' => ['Natalia', 'Cárdenas Vélez', 'natalia.cardenas@empresa.test'],
        'Comercial' => ['Felipe', 'Ospina Mora', 'felipe.ospina@empresa.test'],
        'Financiero' => ['Mónica', 'Salazar Duque', 'monica.salazar@empresa.test'],
    ] as $process => [$name, $lastname, $email]) {
        $model = Process::named($process);
        $directors[$process] = startGuideUser(['name' => $name, 'lastname' => $lastname, 'email' => $email, 'process_id' => $model?->id]);
        $model?->update(['director_id' => $directors[$process]->id]);
    }

    if ($extra) {
        foreach (['Calidad', 'Talento Humano', 'Sistemas', 'Compras', 'Jurídica', 'Tesorería', 'Cartera', 'Auditoría interna', 'Servicio farmacéutico'] as $name) {
            Process::factory()->create(['name' => $name]);
        }

        Process::whereIn('name', ['Cartera', 'Tesorería'])->update(['status' => Status::Inactive]);
        Process::where('name', 'Jurídica')->first()?->delete();
    }

    return $directors;
}

/** Sedes ficticias además de Oficina Principal. */
function startGuideServicePoints(): void
{
    foreach ([
        ['2', 'Farmacia Centro', 'FCEN', ServicePointType::Pharmacy],
        ['3', 'Farmacia Norte', 'FNOR', ServicePointType::Pharmacy],
        ['4', 'Farmacia Sur', 'FSUR', ServicePointType::Pharmacy],
        ['5', 'Bodega de insumos', 'BOD', ServicePointType::Other],
    ] as [$code, $name, $abbreviation, $type]) {
        ServicePoint::factory()->create([
            'code' => $code, 'name' => $name, 'abbreviation' => $abbreviation, 'type' => $type,
            'address' => 'Carrera '.(10 + (int) $code).' # 20-'.(30 + (int) $code), 'phone' => '604789'.str_pad($code, 4, '0', STR_PAD_LEFT),
        ]);
    }
}

it('genera las capturas de los listados estándar', function (): void {
    $this->actingAs(startGuideAdmin());
    startGuideProcesses();

    $page = visit('/processes')->resize(1440, 900);
    $page->script("document.querySelector('.table-toolbar').style.paddingTop = '18px'");

    guideShot($page, 'standard-lists/01-overview', [
        ['.table-actions', '1'],
        ['#data-table-page thead tr:first-child th:nth-child(2)', '2'],
        ['#data-table-page thead tr:nth-child(2) th:nth-child(5)', '3'],
        ['#data-table-page tbody tr.table-danger', '4'],
        ['#data-table-page tbody tr.table-warning', '5'],
        ['.table-footer-start', '6'],
        ['.table-footer-end', '7'],
    ], '#page_layout .card');

    // Menú "Más" (pantallas de menos de 1600 px) con una fila seleccionada.
    startGuideClick($page, '#data-table-page tbody tr:nth-child(3) input[type="checkbox"]');
    startGuideClick($page, '[aria-label="Más acciones"]', 0.5);

    guideShot($page, 'standard-lists/03-more-menu', [
        ['[aria-label="Más acciones"]', '1'], ['.table-actions-menu', '2'],
    ], '#page_layout .card');

    // Confirmación antes de eliminar.
    startGuideClick($page, 'body', 0.3);
    startGuideClick($page, '[aria-label="Eliminar seleccionados"]', 0.8);

    guideShot($page, 'standard-lists/06-confirm-delete', [], '.swal2-popup');

    startGuideClick($page, '.swal2-cancel', 0.8);

    // Auditoría del registro seleccionado.
    startGuideClick($page, '[aria-label="Más acciones"]', 0.5);
    startGuideClickText($page, '.table-actions-menu button', 'Auditoría');

    guideShot($page, 'standard-lists/07-audit', [], '.app-modal.show .modal-content');

    $page->script("document.querySelector('.app-modal.show .btn-close').click()");
    $page->wait(0.5);
    startGuideClick($page, '#data-table-page tbody tr:nth-child(3) input[type="checkbox"]');

    // Configuración de columnas: se oculta "Creado".
    startGuideClick($page, '[aria-label="Configurar columnas"]', 0.5);
    startGuideClick($page, '.table-columns-list label:last-child input');

    guideShot($page, 'standard-lists/04-columns', [
        ['.table-columns-menu', '1'], ['[aria-label="Configurar columnas"]', '2'],
    ], '#page_layout .card');

    startGuideClick($page, '.table-columns-menu .btn-link');
    startGuideClick($page, '[aria-label="Configurar columnas"]', 0.5);

    // Selección de la página y aviso para seleccionar todos los que coinciden.
    startGuideClick($page, '#data-table-page thead input[type="checkbox"]');

    guideShot($page, 'standard-lists/05-selection-banner', [
        ['#data-table-page thead input[type="checkbox"]', '1'], ['.alert-info', '2'],
    ], '#page_layout .card');

    // Barra completa (pantallas anchas) en Agotados, que tiene todas las acciones estándar.
    StockOut::factory()->count(6)->sequence(
        ['generic_code' => '10045', 'generic_name' => 'Acetaminofén 500 mg', 'product_name' => 'Acetaminofén 500 mg tableta x 100', 'brand' => 'Laboratorios Andinos'],
        ['generic_code' => '10233', 'generic_name' => 'Amoxicilina 500 mg', 'product_name' => 'Amoxicilina 500 mg cápsula x 50', 'brand' => 'Farmacéutica del Valle'],
        ['generic_code' => '11870', 'generic_name' => 'Losartán 50 mg', 'product_name' => 'Losartán potásico 50 mg tableta x 30', 'brand' => 'Genéricos Unidos'],
        ['generic_code' => '12054', 'generic_name' => 'Metformina 850 mg', 'product_name' => 'Metformina 850 mg tableta x 30', 'brand' => 'Laboratorios Andinos'],
        ['generic_code' => '13310', 'generic_name' => 'Omeprazol 20 mg', 'product_name' => 'Omeprazol 20 mg cápsula x 30', 'brand' => 'Farmacéutica del Valle'],
        ['generic_code' => '14402', 'generic_name' => 'Salbutamol inhalador', 'product_name' => 'Salbutamol 100 mcg inhalador x 200 dosis', 'brand' => 'Respira Pharma'],
    )->create(['classification' => 'MEDICAMENTOS']);

    $page = visit('/pharmacy/stock-outs')->resize(1680, 900);
    startGuideClick($page, '#data-table-page tbody tr:first-child input[type="checkbox"]');
    startGuideSpreadToolbar($page);
    $page->script("document.querySelector('.table-toolbar').style.maxWidth = '900px'");
    startGuidePad($page, '.table-toolbar', '22px 12px 10px 22px');

    $marks = [];
    foreach (['Agregar', 'Editar seleccionado', 'Duplicar seleccionado', 'Eliminar seleccionados', 'Activar / inactivar seleccionados',
        'Restaurar eliminados seleccionados', 'Auditoría', 'Importar desde Excel / CSV', 'Exportar a Excel', 'Exportar a CSV', 'Limpiar filtros'] as $i => $label) {
        $marks[] = ['[aria-label="'.$label.'"]', (string) ($i + 1)];
    }

    guideShot($page, 'standard-lists/02-toolbar', $marks, '.table-toolbar');
});

it('genera las capturas de usuarios', function (): void {
    $this->actingAs(startGuideAdmin());
    startGuideServicePoints();
    startGuideProcesses(extra: false);

    $pharmacist = Role::create(['name' => 'Regente de farmacia', 'guard_name' => 'web']);
    $buyer = Role::create(['name' => 'Auxiliar de compras', 'guard_name' => 'web']);
    $centro = ServicePoint::where('code', '2')->value('id');

    startGuideUser(['identification' => '1003456789', 'name' => 'Andrés', 'lastname' => 'Pérez Gómez', 'email' => 'andres.perez@empresa.test',
        'role_id' => $pharmacist->id, 'service_point_id' => $centro, 'status' => UserStatus::Inactive]);
    startGuideUser(['identification' => '1067123456', 'name' => 'Sofía', 'lastname' => 'Herrera Lozano', 'email' => 'sofia.herrera@empresa.test',
        'role_id' => $buyer->id, 'process_id' => Process::named('Comercial')?->id, 'can_request_supplies' => true]);
    startGuideUser(['identification' => '1065987321', 'name' => 'Daniel', 'lastname' => 'Quintero Rojas', 'email' => 'daniel.quintero@empresa.test',
        'role_id' => $pharmacist->id, 'service_point_id' => ServicePoint::where('code', '3')->value('id')]);

    $page = visit('/users')->resize(1440, 900);
    startGuideSpreadToolbar($page);

    guideShot($page, 'admin-users/01-list', [
        ['[aria-label="Agregar"]', '1'],
        ['[aria-label="Editar seleccionado"]', '2'],
        ['[aria-label="Habilitar solicitud de suministros"]', '3'],
        ['[aria-label="Deshabilitar solicitud de suministros"]', '4'],
        ['#data-table-page thead tr:nth-child(2) th:nth-child(7)', '5'],
        ['#data-table-page tbody tr.table-warning td:nth-child(2)', '6'],
    ], '#page_layout .card');

    startGuideClick($page, '[aria-label="Agregar"]');
    $page->type('#form-identification', '1067554433')
        ->type('#form-email', 'valentina.mejia@empresa.test')
        ->type('#form-name', 'Valentina')
        ->type('#form-lastname', 'Mejía Castro')
        ->select('#form-service_point_id', (string) $centro)
        ->select('#role_id', (string) $pharmacist->id)
        ->wait(0.8);

    guideShot($page, 'admin-users/02-create', [
        ['#form-identification', '1'], ['#form-email', '2'], ['#form-service_point_id', '3'], ['#role_id', '4'],
        ['#process_id', '5'], ['#position_id', '6'], ['#status', '7'], ['#can_request_supplies', '8'],
    ], '.app-modal.show .modal-content');

    $page->script("document.querySelector('.app-modal.show .btn-close').click()");
    $page->wait(0.5);

    $andres = User::where('email', 'andres.perez@empresa.test')->value('id');
    startGuideDispatch($page, 'open-user-edit', ['userId' => $andres]);

    guideShot($page, 'admin-users/03-edit-status', [['#status', '1']], '.app-modal.show .modal-content');
});

it('genera las capturas de roles y permisos', function (): void {
    $this->actingAs(startGuideAdmin());
    startGuideServicePoints();
    startGuideProcesses(extra: false);

    $pharmacist = Role::create(['name' => 'Regente de farmacia', 'guard_name' => 'web']);
    $pharmacist->syncPermissions(['stock-outs-read', 'pendings-read', 'pendings-create', 'pendings-update', 'pendings-delete',
        'new-products-read', 'new-products-create', 'new-products-update']);
    Role::create(['name' => 'Auxiliar de compras', 'guard_name' => 'web'])
        ->syncPermissions(['products-read', 'commercial-providers-read', 'commercial-providers-create', 'commercial-providers-update']);

    User::where('email', 'like', '%@empresa.test')->where('email', '!=', 'laura.mendez@empresa.test')->get()
        ->each(fn (User $user) => $user->update(['role_id' => $pharmacist->id]));

    $page = visit('/roles')->resize(1440, 900);

    guideShot($page, 'admin-roles/01-cards', [
        ['.roles-grid .col-md-4:first-child .role-card-body', '1'],
        ['.role-add-card .role-card-body', '2'],
    ], '.roles-grid');

    startGuideDispatch($page, 'open-role-create');
    $page->type('#form-name', 'Director de proceso')->wait(0.5);

    guideShot($page, 'admin-roles/02-role-form', [], '.app-modal.show .modal-content');

    $page->script("document.querySelector('.app-modal.show .btn-close').click()");
    $page->wait(0.5);
    startGuideDispatch($page, 'open-role-permissions', ['roleId' => $pharmacist->id]);

    guideShot($page, 'admin-roles/03-permissions', [
        ['.app-modal.show thead tr', '1'],
        ['.app-modal.show tbody tr:nth-child(10)', '2'],
    ], '.app-modal.show .modal-content');
});

it('genera las capturas de procesos y reemplazos', function (): void {
    $this->actingAs(startGuideAdmin());
    $directors = startGuideProcesses(extra: false);

    $substitute = startGuideUser(['name' => 'Carlos', 'lastname' => 'Vargas Peña', 'email' => 'carlos.vargas@empresa.test']);
    $administrative = Process::named('Administrativo');

    $vacation = ProcessSubstitution::create([
        'process_id' => $administrative->id, 'substitute_id' => $substitute->id,
        'starts_on' => '2026-12-14', 'ends_on' => '2027-01-08', 'observations' => 'Vacaciones de la directora administrativa',
    ]);
    ProcessSubstitution::create([
        'process_id' => Process::named('Gerencia')->id, 'substitute_id' => $directors['Financiero']->id,
        'starts_on' => '2026-11-01', 'ends_on' => null, 'observations' => 'Financiero aprueba las requisiciones de Gerencia',
    ]);
    ProcessSubstitution::create([
        'process_id' => Process::named('Comercial')->id, 'substitute_id' => $directors['Administrativo']->id,
        'starts_on' => '2026-10-06', 'ends_on' => '2026-10-17', 'observations' => 'Licencia del director comercial',
    ]);

    $page = visit('/processes')->resize(1440, 900);
    startGuideSpreadToolbar($page);

    guideShot($page, 'admin-processes/01-list', [
        ['[aria-label="Agregar"]', '1'], ['[aria-label="Editar seleccionado"]', '2'],
        ['#data-table-page thead tr:first-child th:nth-child(3)', '3'],
    ], '#page_layout .card');

    startGuideDispatch($page, 'open-process-edit', ['processId' => $administrative->id]);

    guideShot($page, 'admin-processes/02-process-form', [
        ['#process-name', '1'], ['#process-director', '2'], ['.app-modal.show .modal-body .btn-link', '3'],
    ], '.app-modal.show .modal-content');

    $page = visit('/process-substitutions')->resize(1440, 900);
    startGuideSpreadToolbar($page);

    guideShot($page, 'admin-processes/03-substitutions', [
        ['[aria-label="Agregar"]', '1'],
        ['#data-table-page thead tr:first-child th:nth-child(4)', '2'],
        ['#data-table-page thead tr:first-child th:nth-child(5)', '3'],
        ['#data-table-page tbody .badge.bg-info', '4'],
    ], '#page_layout .card');

    startGuideDispatch($page, 'open-process-substitution-edit', ['substitutionId' => $vacation->id]);

    guideShot($page, 'admin-processes/04-substitution-form', [
        ['#substitution-process', '1'], ['#substitution-user', '2'], ['#substitution-starts-on', '3'], ['#substitution-ends-on', '4'],
    ], '.app-modal.show .modal-content');
});

it('genera las capturas de sedes y características', function (): void {
    $this->actingAs(startGuideAdmin());
    startGuideServicePoints();
    $this->seed([CharacteristicOptionSeeder::class]);

    $page = visit('/service-points')->resize(1440, 900);

    guideShot($page, 'admin-catalogs/01-service-points', [
        ['#page_layout .card-header .btn-primary', '1'],
        ['#page_layout input[type="search"]', '2'],
        ['#page_layout .card-body .text-md-end', '3'],
        ['#page_layout tbody tr:first-child td:last-child', '4'],
    ], '#page_layout .card');

    startGuideDispatch($page, 'open-service-point-edit', ['servicePointId' => ServicePoint::where('code', '2')->value('id')]);

    guideShot($page, 'admin-catalogs/02-service-point-form', [
        ['.app-modal.show #form-code', '1'], ['.app-modal.show #form-abbreviation', '2'], ['.app-modal.show #form-type', '3'],
    ], '.app-modal.show .modal-content');

    $page = visit('/characteristics')->resize(1440, 900);
    startGuideClickText($page, '#page_layout tbody tr', 'Condición de pago');

    guideShot($page, 'admin-catalogs/03-characteristics', [
        ['#page_layout tr.table-active', '1'],
        ['#page_layout .col-md-6:nth-child(2) .card', '2'],
    ], '#page_layout');

    startGuideDispatch($page, 'open-characteristic-edit', ['characteristicId' => Characteristic::where('name', 'Condición de pago')->value('id')]);

    guideShot($page, 'admin-catalogs/04-characteristic-form', [
        ['.app-modal.show #form-name', '1'], ['.app-modal.show #form-code', '2'], ['.app-modal.show #form-status', '3'],
    ], '.app-modal.show .modal-content');
});

it('genera las capturas de los datos de la empresa', function (): void {
    $this->actingAs(startGuideAdmin());

    $officer = startGuideUser(['name' => 'Diana', 'lastname' => 'Rojas Arango', 'email' => 'diana.rojas@empresa.test', 'identification' => '1064321987']);

    $settings = app(CompanySettings::class);
    $settings->fill([
        'legal_name' => 'Distribuidora Farmacéutica Ejemplo S.A.S.', 'nit' => '900123456-7',
        'address' => 'Calle 27 # 4-15', 'city' => 'Montería', 'phone' => '6047891234',
        'email' => 'contacto@empresa.test', 'website' => 'https://www.empresa.test',
        'compliance_officer_id' => $officer->id, 'minimum_wages' => ['2025' => 1423500, '2026' => 1750905],
        'provider_form_wage_multiplier' => 2,
    ])->save();

    $this->seed([PositionSeeder::class, ProviderVerifierSeeder::class]);

    $page = visit('/company-settings')->resize(1440, 900);

    guideShot($page, 'admin-company-settings/01-view', [
        ['.document-header-actions .btn', '1'],
        ['.document-section:has(.bi-shield-check)', '2'],
        ['.document-section:has(.bi-cash-coin)', '3'],
    ], '.document-header');

    startGuideClick($page, '.document-header-actions .btn');

    guideShot($page, 'admin-company-settings/02-edit', [
        ['#company-legal-name', '1'], ['#company-logo', '2'], ['#company-wage-multiplier', '3'],
        ['#company-wage-year-0', '4'], ['.app-modal.show [wire\:click="addMinimumWage"]', '5'],
        ['#form-compliance_officer_id', '6'],
    ], '.app-modal.show .modal-content');

    // Revisión del FO-300-01: al final del formulario (el cuerpo del modal tiene scroll).
    startGuidePad($page, '.app-modal.show .form-section:has(#company-management-approval)');
    $page->script("document.querySelector('.app-modal.show .modal-body').scrollTop = 100000");
    $page->wait(0.8);
    guideShot($page, 'admin-company-settings/03-review', [
        ['#company-management-approval', '1'], ['#company-verifiers-administrative', '2'], ['#company-verifiers-commercial', '3'],
    ], '.app-modal.show .form-section:has(#company-management-approval)');
});

it('genera las capturas de la ubicación geográfica', function (): void {
    $this->actingAs(startGuideAdmin());

    $colombia = GeographicLocation::factory()->create(['code' => 'CO', 'name' => 'Colombia', 'population' => 52695952]);
    $cordoba = GeographicLocation::factory()->department($colombia)->create(['code' => '23', 'name' => 'Córdoba', 'population' => 1922109]);
    $monteria = GeographicLocation::factory()->city($cordoba)->create(['code' => '23001', 'name' => 'Montería', 'population' => 505334]);
    GeographicLocation::factory()->city($cordoba)->create(['code' => '23162', 'name' => 'Cereté', 'population' => 98457]);
    GeographicLocation::factory()->city($cordoba)->create(['code' => '23807', 'name' => 'Tierralta', 'status' => 'inactive']);
    $antioquia = GeographicLocation::factory()->department($colombia)->create(['code' => '05', 'name' => 'Antioquia']);
    GeographicLocation::factory()->city($antioquia)->create(['code' => '05999', 'name' => 'Ciudad de prueba'])->delete();
    $australia = GeographicLocation::factory()->create(['code' => 'AU', 'name' => 'Australia']);
    $state = GeographicLocation::factory()->department($australia)->create(['code' => 'AU-NSW', 'name' => 'Nueva Gales del Sur']);
    GeographicLocation::factory()->city($state)->create(['code' => 'AU-SYD', 'name' => 'Sídney']);
    Provider::factory()->create(['name' => 'Papelería del Sinú S.A.S.', 'municipality_id' => $monteria->id]);

    $page = visit('/geographic-locations')->resize(1440, 900);
    startGuideSpreadToolbar($page);

    guideShot($page, 'admin-geographic-locations/01-list', [
        ['[aria-label="Agregar"]', '1'],
        ['#data-table-page thead tr:nth-child(2) th:nth-child(2)', '2'],
        ['#data-table-page thead tr:nth-child(2) th:nth-child(5)', '3'],
        ['#data-table-page tbody tr.table-warning', '4'],
        ['#data-table-page tbody tr.table-danger', '5'],
    ], '#page_layout .card');

    startGuideDispatch($page, 'open-geographic-location-create');
    $page->select('#geo-level', 'city');
    $page->wait(1);
    $page->select('#geo-parent', (string) $cordoba->id);
    $page->type('#geo-code', '23417');
    $page->type('#geo-name', 'Lorica');

    guideShot($page, 'admin-geographic-locations/02-form', [
        ['.app-modal.show #geo-level', '1'], ['.app-modal.show #geo-code', '2'], ['.app-modal.show #geo-parent', '3'],
        ['.app-modal.show #geo-name', '4'], ['.app-modal.show #geo-population', '5'],
    ], '.app-modal.show .modal-content');

    $page = visit('/geographic-locations')->resize(1440, 900);
    startGuideDispatch($page, 'open-geographic-location-edit', ['locationId' => $monteria->id]);

    guideShot($page, 'admin-geographic-locations/03-locked', [
        ['.app-modal.show .alert-info', '1'],
    ], '.app-modal.show .modal-content');
});
