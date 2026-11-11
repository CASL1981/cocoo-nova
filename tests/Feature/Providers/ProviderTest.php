<?php

use App\Actions\Providers\ProviderWorkflow;
use App\Actions\Providers\SaveProviderVersion;
use App\Enums\GeographicLevel;
use App\Enums\ProviderLinkageStatus;
use App\Enums\ProviderScope;
use App\Enums\ProviderVersionStatus;
use App\Enums\RiskLevel;
use App\Enums\Status;
use App\Livewire\Providers\ProviderFormModal;
use App\Models\Characteristic;
use App\Models\EconomicActivity;
use App\Models\GeographicLocation;
use App\Models\Process;
use App\Models\ProcessSubstitution;
use App\Models\Provider;
use App\Models\ProviderLinkage;
use App\Models\ProviderVersion;
use App\Models\ProviderVersionDocument;
use App\Models\User;
use App\Settings\CompanySettings;
use Database\Seeders\CharacteristicOptionSeeder;
use Database\Seeders\ProcessSeeder;
use Database\Seeders\ProviderSeeder;
use Database\Seeders\ReferenceCatalogSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed([ProcessSeeder::class, CharacteristicOptionSeeder::class]);

    $colombia = GeographicLocation::factory()->create(['code' => 'CO', 'name' => 'Colombia']);
    $department = GeographicLocation::factory()->department($colombia)->create(['code' => '23', 'name' => 'Córdoba']);
    $this->catalog = [
        'department' => $department,
        'municipality' => GeographicLocation::factory()->city($department)->create(['code' => '23001', 'name' => 'Montería']),
        'country' => $colombia,
        'activity' => EconomicActivity::create(['code' => '4645', 'description' => 'Comercio al por mayor de productos farmacéuticos']),
    ];
});

/** Usuario director del proceso indicado (Comercial, Administrativo o Gerencia). */
function directorOf(string $processName, array $permissions = []): User
{
    $user = userWith($permissions);
    Process::where('name', $processName)->update(['director_id' => $user->id]);

    return $user;
}

/** Proveedor con su versión abierta en el estado indicado y vinculación al proceso. */
function providerInStatus(ProviderVersionStatus $status, ProviderScope $scope = ProviderScope::Commercial, bool $pep = false): ProviderLinkage
{
    $factory = ProviderVersion::factory()->status($status);
    $version = ($pep ? $factory->pep() : $factory)->create();

    return ProviderLinkage::factory()->scope($scope)->create(['provider_id' => $version->provider_id]);
}

it('niega el listado sin permiso y separa los procesos', function (): void {
    $this->actingAs(userWith())->get(route('commercial-providers.index'))->assertForbidden();

    $commercial = userWith('commercial-providers-read');
    $this->actingAs($commercial)->get(route('commercial-providers.index'))->assertOk();
    $this->actingAs($commercial)->get(route('administrative-providers.index'))->assertForbidden();
});

it('lista solo las vinculaciones del proceso de la pantalla', function (): void {
    $commercial = providerInStatus(ProviderVersionStatus::Draft, ProviderScope::Commercial);
    $administrative = providerInStatus(ProviderVersionStatus::Draft, ProviderScope::Administrative);

    Livewire::actingAs(userWith('commercial-providers-read'))
        ->test('shopping::pages.commercial-provider-index')
        ->assertSee($commercial->provider->name)
        ->assertDontSee($administrative->provider->name);
});

it('no abre en un módulo la vinculación del otro proceso', function (): void {
    $administrative = providerInStatus(ProviderVersionStatus::Draft, ProviderScope::Administrative);

    $this->actingAs(userWith(['commercial-providers-read', 'administrative-providers-read']))
        ->get(route('commercial-providers.show', $administrative))
        ->assertNotFound();
});

it('registra una persona jurídica en Borrador con su vinculación pendiente', function (): void {
    Livewire::actingAs(userWith(['commercial-providers-read', 'commercial-providers-create']))
        ->test(ProviderFormModal::class, ['scope' => 'commercial'])
        ->call('openCreate')
        ->set('form.person_type', 'legal')
        ->set('form.document_type', 'NIT')
        ->set('form.identification', '900123456')
        ->set('form.name', 'DROGUERÍA DE PRUEBA SAS')
        ->set('form.email', 'ventas@example.com')
        ->set('form.phone', '3001234567')
        ->set('form.address', 'Calle 1 # 2-3')
        ->set('form.department_id', (string) $this->catalog['department']->id)
        ->set('form.municipality_id', (string) $this->catalog['municipality']->id)
        ->set('form.country_id', (string) $this->catalog['country']->id)
        ->set('form.economic_activity_id', (string) $this->catalog['activity']->id)
        ->set('form.tax_regime', 'Responsable de IVA')
        // Tipo de empresa desde Características (incluye Unión temporal) y RL con NIT.
        ->set('form.company_type', 'Unión temporal')
        ->set('form.legal_representative_name', 'Ana Pérez')
        ->set('form.legal_representative_document_type', 'NIT')
        ->set('form.legal_representative_identification', '12345678')
        ->set('form.monthly_income', '50000000')
        ->set('form.total_assets', '900000000')
        ->set('form.total_liabilities', '100000000')
        ->set('form.total_equity', '800000000')
        ->set('form.has_foreign_accounts', true)
        // Sin marcar "Tiene relación con un PEP", los datos del PEP relacionado no se guardan.
        ->set('form.pep_relation_name', 'Dato sobrante')
        ->set('form.contacts.billing.name', 'Facturación SAS')
        // El cargo es obligatorio en todo contacto diligenciado.
        ->call('save')
        ->assertHasErrors(['form.contacts.billing.job_title' => 'required_with'])
        ->set('form.contacts.billing.job_title', 'Auxiliar contable')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $provider = Provider::where('identification', '900123456')->firstOrFail();
    $version = $provider->versions()->firstOrFail();

    expect($provider->risk_level)->toBe(RiskLevel::Low)
        ->and($version->status)->toBe(ProviderVersionStatus::Draft)
        ->and($version->company_type)->toBe('Unión temporal')
        ->and($version->legal_representative_document_type?->value)->toBe('NIT')
        // Cuentas en moneda extranjera aplica también a persona jurídica.
        ->and($version->has_foreign_accounts)->toBeTrue()
        ->and($version->pep_relation_name)->toBeNull()
        ->and($version->contacts()->count())->toBe(1)
        ->and($version->contacts()->value('job_title'))->toBe('Auxiliar contable')
        ->and($provider->linkages()->first()->status)->toBe(ProviderLinkageStatus::Pending)
        ->and($provider->linkages()->first()->scope)->toBe(ProviderScope::Commercial);
});

it('exige los campos de la persona natural', function (): void {
    Livewire::actingAs(userWith(['commercial-providers-read', 'commercial-providers-create']))
        ->test(ProviderFormModal::class, ['scope' => 'commercial'])
        ->call('openCreate')
        ->set('form.person_type', 'natural')
        ->assertSet('form.document_type', 'CC')
        ->set('form.occupation', 'Otro')
        ->call('save')
        ->assertHasErrors(['form.birth_date', 'form.document_issued_on', 'form.education_level', 'form.occupation_other', 'form.residence_zone']);
});

it('ofrece vincular un proveedor existente y abre una actualización para el nuevo proceso', function (): void {
    $version = ProviderVersion::factory()->approved()->create(['created_at' => now()->subYear()]);
    ProviderLinkage::factory()->scope(ProviderScope::Administrative)->active()->create(['provider_id' => $version->provider_id]);

    Livewire::actingAs(userWith(['commercial-providers-read', 'commercial-providers-create']))
        ->test(ProviderFormModal::class, ['scope' => 'commercial'])
        ->call('openCreate')
        ->set('form.identification', $version->identification)
        ->assertSet('existingProviderId', $version->provider_id)
        ->call('linkExisting')
        ->assertRedirect();

    $provider = $version->provider->fresh();

    expect($provider->linkages()->where('scope', ProviderScope::Commercial)->first()->status)->toBe(ProviderLinkageStatus::Pending)
        ->and($provider->openVersion()->first()->number)->toBe(2)
        ->and($provider->openVersion()->first()->request_type->value)->toBe('update');
});

it('exige las declaraciones para enviar a verificación', function (): void {
    $linkage = providerInStatus(ProviderVersionStatus::Draft);
    $linkage->provider->versions()->update(['accepts_funds_origin' => false]);

    Livewire::actingAs(userWith(['commercial-providers-read', 'commercial-providers-update']))
        ->test('shopping::pages.commercial-provider-show', ['linkage' => $linkage])
        ->call('submit');

    expect($linkage->provider->versions()->first()->status)->toBe(ProviderVersionStatus::Draft);
});

it('completa el flujo: enviar, verificar y aprobar por el director Comercial', function (): void {
    $linkage = providerInStatus(ProviderVersionStatus::Draft);

    Livewire::actingAs(userWith(['commercial-providers-read', 'commercial-providers-update']))
        ->test('shopping::pages.commercial-provider-show', ['linkage' => $linkage])
        ->call('submit');

    Livewire::actingAs(userWith(['commercial-providers-read', 'commercial-providers-process']))
        ->test('shopping::pages.commercial-provider-show', ['linkage' => $linkage])
        ->set('reviewNotes', 'RUT y cámara de comercio verificados')
        ->call('verify');

    $version = $linkage->provider->versions()->first();
    expect($version->status)->toBe(ProviderVersionStatus::Approval);

    // El director Administrativo no aprueba proveedores comerciales.
    Livewire::actingAs(directorOf('Administrativo', ['commercial-providers-read']))
        ->test('shopping::pages.commercial-provider-show', ['linkage' => $linkage])
        ->call('approve')
        ->assertForbidden();

    Livewire::actingAs(directorOf('Comercial', ['commercial-providers-read']))
        ->test('shopping::pages.commercial-provider-show', ['linkage' => $linkage])
        ->call('approve');

    $provider = $linkage->provider->fresh();

    expect($version->fresh()->status)->toBe(ProviderVersionStatus::Approved)
        ->and($provider->current_version_id)->toBe($version->id)
        ->and($provider->next_update_on->toDateString())->toBe(now()->addYear()->toDateString())
        ->and($linkage->fresh()->status)->toBe(ProviderLinkageStatus::Active);
});

it('acepta la aprobación de un reemplazo vigente del director', function (): void {
    $linkage = providerInStatus(ProviderVersionStatus::Approval, ProviderScope::Administrative);
    $process = Process::where('name', 'Administrativo')->firstOrFail();
    $substitute = userWith('administrative-providers-read');
    ProcessSubstitution::factory()->create(['process_id' => $process->id, 'substitute_id' => $substitute->id, 'starts_on' => today()->subDay(), 'ends_on' => null]);

    Livewire::actingAs($substitute)
        ->test('administrative::pages.administrative-provider-show', ['linkage' => $linkage])
        ->call('approve');

    expect($linkage->fresh()->status)->toBe(ProviderLinkageStatus::Active);
});

it('pasa un PEP por el oficial de cumplimiento y lo aprueba el director del proceso', function (): void {
    $linkage = providerInStatus(ProviderVersionStatus::Verification, pep: true);
    $officer = userWith('commercial-providers-read');
    $settings = app(CompanySettings::class);
    $settings->compliance_officer_id = $officer->id;
    $settings->save();

    Livewire::actingAs(userWith(['commercial-providers-read', 'commercial-providers-process']))
        ->test('shopping::pages.commercial-provider-show', ['linkage' => $linkage])
        ->call('verify');

    $version = $linkage->provider->versions()->first();
    expect($version->status)->toBe(ProviderVersionStatus::Compliance);

    Livewire::actingAs($officer)
        ->test('shopping::pages.commercial-provider-show', ['linkage' => $linkage])
        ->call('certifyCompliance');

    // Siempre aprueba el director del proceso; la Gerencia firma en físico (por defecto) y no bloquea.
    Livewire::actingAs(directorOf('Gerencia', ['commercial-providers-read']))
        ->test('shopping::pages.commercial-provider-show', ['linkage' => $linkage])
        ->call('approve')
        ->assertForbidden();

    Livewire::actingAs(directorOf('Comercial', ['commercial-providers-read']))
        ->test('shopping::pages.commercial-provider-show', ['linkage' => $linkage])
        ->call('approve');

    expect($version->fresh()->status)->toBe(ProviderVersionStatus::Approved)
        ->and($linkage->provider->fresh()->risk_level)->toBe(RiskLevel::High);
});

it('exige la firma de cada proceso cuando el proveedor tiene dos vinculaciones', function (): void {
    $linkage = providerInStatus(ProviderVersionStatus::Approval);
    $administrative = ProviderLinkage::factory()->scope(ProviderScope::Administrative)->create(['provider_id' => $linkage->provider_id]);
    $version = $linkage->provider->versions()->first();

    app(ProviderWorkflow::class)->approve($version, directorOf('Comercial'), null);
    expect($version->fresh()->status)->toBe(ProviderVersionStatus::Approval);

    app(ProviderWorkflow::class)->approve($version, directorOf('Administrativo'), null);
    expect($version->fresh()->status)->toBe(ProviderVersionStatus::Approved)
        ->and($administrative->fresh()->status)->toBe(ProviderLinkageStatus::Active);
});

it('rechaza con motivo obligatorio y deja la vinculación rechazada', function (): void {
    $linkage = providerInStatus(ProviderVersionStatus::Verification);

    $component = Livewire::actingAs(userWith(['commercial-providers-read', 'commercial-providers-process']))
        ->test('shopping::pages.commercial-provider-show', ['linkage' => $linkage])
        ->call('reject')
        ->assertHasErrors(['reviewNotes']);

    $component->set('reviewNotes', 'El RUT no corresponde al NIT')->call('reject')->assertHasNoErrors();

    expect($linkage->provider->versions()->first()->status)->toBe(ProviderVersionStatus::Rejected)
        ->and($linkage->fresh()->status)->toBe(ProviderLinkageStatus::Rejected);
});

it('devuelve a Borrador solo con permiso de reversar', function (): void {
    $linkage = providerInStatus(ProviderVersionStatus::Approval);

    Livewire::actingAs(userWith(['commercial-providers-read', 'commercial-providers-process']))
        ->test('shopping::pages.commercial-provider-show', ['linkage' => $linkage])
        ->call('returnToDraft')
        ->assertForbidden();

    Livewire::actingAs(userWith(['commercial-providers-read', 'commercial-providers-reverse']))
        ->test('shopping::pages.commercial-provider-show', ['linkage' => $linkage])
        ->call('returnToDraft');

    expect($linkage->provider->versions()->first()->status)->toBe(ProviderVersionStatus::Draft);
});

it('abre la actualización anual copiando la versión vigente con sus líneas', function (): void {
    $version = ProviderVersion::factory()->approved()->create(['created_at' => now()->subYear()]);
    $version->contacts()->create(['role' => 'billing', 'name' => 'Cartera']);
    $version->shareholders()->create(['document_type' => 'CC', 'identification' => '123', 'name' => 'Socio', 'ownership_percentage' => 60]);
    $linkage = ProviderLinkage::factory()->active()->create(['provider_id' => $version->provider_id]);

    Livewire::actingAs(userWith(['commercial-providers-read', 'commercial-providers-create']))
        ->test('shopping::pages.commercial-provider-show', ['linkage' => $linkage])
        ->call('startUpdate');

    $draft = $version->provider->openVersion()->firstOrFail();

    expect($draft->number)->toBe(2)
        ->and($draft->status)->toBe(ProviderVersionStatus::Draft)
        ->and($draft->contacts()->count())->toBe(1)
        ->and($draft->shareholders()->count())->toBe(1)
        ->and($version->fresh()->status)->toBe(ProviderVersionStatus::Approved);
});

it('registra accionistas sin superar el 100 % de participación', function (): void {
    $linkage = providerInStatus(ProviderVersionStatus::Draft);
    $page = Livewire::actingAs(userWith(['commercial-providers-read', 'commercial-providers-update']))
        ->test('shopping::pages.commercial-provider-show', ['linkage' => $linkage]);

    $page->set('shareholderForm.document_type', 'CC')->set('shareholderForm.identification', '111')
        ->set('shareholderForm.name', 'Socio Uno')->set('shareholderForm.ownership_percentage', '70')
        ->call('addShareholder')->assertHasNoErrors();

    $page->set('shareholderForm.document_type', 'CC')->set('shareholderForm.identification', '222')
        ->set('shareholderForm.name', 'Socio Dos')->set('shareholderForm.ownership_percentage', '40')
        ->call('addShareholder')->assertHasErrors(['shareholderForm.ownership_percentage']);

    expect($linkage->provider->versions()->first()->shareholders()->count())->toBe(1);
});

it('exige el representante legal solo al accionista con NIT', function (): void {
    $linkage = providerInStatus(ProviderVersionStatus::Draft);
    $page = Livewire::actingAs(userWith(['commercial-providers-read', 'commercial-providers-update']))
        ->test('shopping::pages.commercial-provider-show', ['linkage' => $linkage]);

    // NIT = persona jurídica: nombre y cédula del representante obligatorios.
    $page->set('shareholderForm.document_type', 'NIT')->set('shareholderForm.identification', '900111222')
        ->set('shareholderForm.name', 'Inversiones Socias SAS')->set('shareholderForm.ownership_percentage', '30')
        ->assertSee('Cédula del representante')
        ->call('addShareholder')
        ->assertHasErrors(['shareholderForm.legal_representative_name' => 'required', 'shareholderForm.legal_representative_identification' => 'required'])
        ->set('shareholderForm.legal_representative_name', 'Ana Gómez')->set('shareholderForm.legal_representative_identification', '1065000111')
        ->call('addShareholder')->assertHasNoErrors();

    // Otro documento = persona natural: al cambiar el tipo se descarta y no se admite representante.
    $page->set('shareholderForm.document_type', 'NIT')->set('shareholderForm.legal_representative_name', 'Sobrante')
        ->set('shareholderForm.document_type', 'CC')
        ->assertSet('shareholderForm.legal_representative_name', null)
        ->assertDontSee('Cédula del representante')
        ->set('shareholderForm.legal_representative_identification', '123')
        ->set('shareholderForm.identification', '10777888')->set('shareholderForm.name', 'Carlos Pérez')->set('shareholderForm.ownership_percentage', '20')
        ->call('addShareholder')->assertHasErrors(['shareholderForm.legal_representative_identification' => 'prohibited']);

    $shareholder = $linkage->provider->versions()->first()->shareholders()->firstOrFail();
    expect($shareholder->legal_representative_identification)->toBe('1065000111');
});

it('carga y descarga anexos en el disco privado', function (): void {
    Storage::fake('local');
    $linkage = providerInStatus(ProviderVersionStatus::Draft);

    $page = Livewire::actingAs(userWith(['commercial-providers-read', 'commercial-providers-update']))
        ->test('shopping::pages.commercial-provider-show', ['linkage' => $linkage])
        ->set('documentForm.type', ProviderVersionDocument::CHAMBER_OF_COMMERCE_TYPE)
        ->set('documentForm.file', UploadedFile::fake()->create('camara.pdf', 100, 'application/pdf'))
        ->call('addDocument')
        ->assertHasErrors(['documentForm.issued_on'])
        ->set('documentForm.issued_on', today()->toDateString())
        ->call('addDocument')
        ->assertHasNoErrors();

    $document = $linkage->provider->versions()->first()->documents()->firstOrFail();
    Storage::disk('local')->assertExists($document->path);

    $page->call('downloadDocument', $document->id)->assertFileDownloaded('camara.pdf');
});

it('acepta los tipos de anexo que se agregan en Características y rechaza los inactivos', function (): void {
    Storage::fake('local');
    $linkage = providerInStatus(ProviderVersionStatus::Draft);
    $characteristic = Characteristic::where('code', ProviderVersionDocument::TYPE_CHARACTERISTIC_CODE)->firstOrFail();
    $characteristic->options()->create(['name' => 'Certificación bancaria', 'status' => Status::Active]);
    $characteristic->options()->where('name', 'Otro')->update(['status' => Status::Inactive]);

    $page = Livewire::actingAs(userWith(['commercial-providers-read', 'commercial-providers-update']))
        ->test('shopping::pages.commercial-provider-show', ['linkage' => $linkage])
        ->set('documentForm.type', 'Otro')
        ->set('documentForm.file', UploadedFile::fake()->create('otro.pdf', 50, 'application/pdf'))
        ->call('addDocument')
        ->assertHasErrors(['documentForm.type' => 'in'])
        // Un tipo nuevo no exige fecha de expedición (solo la cámara de comercio).
        ->set('documentForm.type', 'Certificación bancaria')
        ->call('addDocument')
        ->assertHasNoErrors();

    expect($linkage->provider->versions()->first()->documents()->value('type'))->toBe('Certificación bancaria');
});

it('oculta los datos sensibles a quien solo puede leer', function (): void {
    $linkage = providerInStatus(ProviderVersionStatus::Draft);
    $linkage->provider->versions()->update(['monthly_income' => 12345678]);
    $income = '$ 12.345.678,00';

    Livewire::actingAs(userWith('commercial-providers-read'))
        ->test('shopping::pages.commercial-provider-show', ['linkage' => $linkage])
        ->assertSee('Datos reservados')
        ->assertDontSee($income);

    Livewire::actingAs(userWith(['commercial-providers-read', 'commercial-providers-process']))
        ->test('shopping::pages.commercial-provider-show', ['linkage' => $linkage])
        ->assertSee($income);
});

it('descarga el PDF del formato', function (): void {
    $linkage = providerInStatus(ProviderVersionStatus::Draft);
    $version = $linkage->provider->versions()->first();

    Livewire::actingAs(userWith('commercial-providers-read'))
        ->test('shopping::pages.commercial-provider-show', ['linkage' => $linkage])
        ->call('downloadPdf', $version->id)
        ->assertFileDownloaded($version->pdfFileName());
});

it('activa e inactiva solo vinculaciones aprobadas', function (): void {
    $pending = providerInStatus(ProviderVersionStatus::Draft);
    $active = ProviderLinkage::factory()->active()->create();

    Livewire::actingAs(userWith(['commercial-providers-read', 'commercial-providers-toggle']))
        ->test('shopping::pages.commercial-provider-index')
        ->set('selected', [(string) $pending->id, (string) $active->id])
        ->call('toggleSelected');

    expect($pending->fresh()->status)->toBe(ProviderLinkageStatus::Pending)
        ->and($active->fresh()->status)->toBe(ProviderLinkageStatus::Inactive);
});

it('deja de considerar PEP a quien terminó su cargo hace más de dos años', function (): void {
    expect(SaveProviderVersion::evaluatePep(['declares_pep' => true, 'pep_ends_on' => today()->subYears(3)->toDateString()]))->toBeFalse()
        ->and(SaveProviderVersion::evaluatePep(['declares_pep' => true, 'pep_ends_on' => today()->subYear()->toDateString()]))->toBeTrue()
        ->and(SaveProviderVersion::evaluatePep(['has_pep_relationship' => true]))->toBeTrue();
});

it('carga los catálogos oficiales y los proveedores del sistema anterior', function (): void {
    foreach ([GeographicLevel::City, GeographicLevel::Department, GeographicLevel::Country] as $level) {
        GeographicLocation::query()->where('level', $level)->forceDelete();
    }
    EconomicActivity::query()->delete();

    $this->seed([ReferenceCatalogSeeder::class, ProviderSeeder::class]);

    expect(GeographicLocation::where('level', GeographicLevel::Country)->count())->toBe(250)
        ->and(GeographicLocation::where('level', GeographicLevel::Department)->where('parent_id', GeographicLocation::colombiaId())->count())->toBe(33)
        ->and(GeographicLocation::where('level', GeographicLevel::City)->count())->toBe(1122)
        ->and(EconomicActivity::where('code', '4645')->exists())->toBeTrue()
        ->and(Provider::count())->toBe(67)
        ->and(Provider::where('identification', '812001561')->exists())->toBeTrue()
        ->and(ProviderLinkage::where('status', ProviderLinkageStatus::Active)->where('scope', ProviderScope::Administrative)->count())->toBe(67);
});

it('ofrece abrir la actualización al editar un proveedor aprobado', function (): void {
    $version = ProviderVersion::factory()->approved()->create(['created_at' => now()->subYear()]);
    $linkage = ProviderLinkage::factory()->scope(ProviderScope::Administrative)->active()->create(['provider_id' => $version->provider_id]);
    $user = userWith(['administrative-providers-read', 'administrative-providers-create', 'administrative-providers-update']);

    Livewire::actingAs($user)
        ->test('administrative::pages.administrative-provider-index')
        ->set('selected', [(string) $linkage->id])
        ->assertSet('selected', [(string) $linkage->id])
        ->call('viewSelected')
        ->assertRedirect(route('administrative-providers.show', $linkage));

    Livewire::actingAs($user)
        ->test(ProviderFormModal::class, ['scope' => 'administrative'])
        ->call('openEdit', $linkage->id)
        ->assertSet('updateLinkageId', $linkage->id)
        ->assertSee('Abrir actualización')
        ->call('startUpdate')
        ->assertSet('updateLinkageId', null)
        ->assertSet('form.identification', $version->identification);

    expect($version->provider->openVersion()->firstOrFail()->number)->toBe(2);
});

it('pide el cargo público y su inicio si administra recursos públicos, se declara PEP o ambas', function (): void {
    $form = fn () => Livewire::actingAs(userWith(['commercial-providers-read', 'commercial-providers-create']))
        ->test(ProviderFormModal::class, ['scope' => 'commercial'])
        ->call('openCreate')
        ->set('form.person_type', 'legal');

    foreach ([['manages_public_resources'], ['declares_pep'], ['manages_public_resources', 'declares_pep']] as $answers) {
        $component = $form();
        foreach ($answers as $answer) {
            $component->set("form.{$answer}", true);
        }

        $component->assertSee('Cargo público')
            ->call('save')
            ->assertHasErrors(['form.public_office' => 'required', 'form.pep_starts_on' => 'required']);
    }

    // Sin ninguna de las dos respuestas, el cargo no se pide.
    $form()->assertDontSee('Cargo público')->call('save')->assertHasNoErrors(['form.public_office', 'form.pep_starts_on']);
});

it('pide los datos del PEP con quien tiene relación y solo documento, nombre y parentesco del familiar PEP', function (): void {
    Livewire::actingAs(userWith(['commercial-providers-read', 'commercial-providers-create']))
        ->test(ProviderFormModal::class, ['scope' => 'commercial'])
        ->call('openCreate')
        ->set('form.has_pep_relationship', true)
        ->assertSee('Identificación del PEP')
        ->call('save')
        ->assertHasErrors([
            'form.pep_relation_name' => 'required', 'form.pep_relation_identification' => 'required',
            'form.pep_relation_office' => 'required', 'form.pep_relation_starts_on' => 'required',
            'form.pep_relation_relationship' => 'required',
        ])
        ->assertHasNoErrors(['form.pep_relation_ends_on'])
        ->set('form.has_pep_relationship', false)
        ->assertDontSee('Identificación del PEP');

    $linkage = providerInStatus(ProviderVersionStatus::Draft);
    $page = Livewire::actingAs(userWith(['commercial-providers-read', 'commercial-providers-update']))
        ->test('shopping::pages.commercial-provider-show', ['linkage' => $linkage])
        ->set('relativeForm.document_type', '')
        ->set('relativeForm.name', 'Laura Martínez')->set('relativeForm.relationship', 'Hermano(a)')
        ->call('addRelative')
        ->assertHasErrors(['relativeForm.document_type' => 'required', 'relativeForm.identification' => 'required'])
        ->set('relativeForm.document_type', 'CC')->set('relativeForm.identification', '1065123456')
        ->call('addRelative')->assertHasNoErrors();

    expect($linkage->provider->versions()->first()->pepRelatives()->firstOrFail()->only(['identification', 'name', 'relationship']))
        ->toBe(['identification' => '1065123456', 'name' => 'Laura Martínez', 'relationship' => 'Hermano(a)']);
});
