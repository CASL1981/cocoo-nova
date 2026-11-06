<?php

use App\Actions\Providers\ProviderWorkflow;
use App\Actions\Providers\SaveProviderBasic;
use App\Enums\ProviderFormStatus;
use App\Enums\ProviderLinkageStatus;
use App\Enums\ProviderPurchaseStatus;
use App\Enums\ProviderScope;
use App\Enums\ProviderVersionStatus;
use App\Livewire\Providers\ProviderBasicModal;
use App\Livewire\Providers\ProviderFormModal;
use App\Models\GeographicLocation;
use App\Models\Process;
use App\Models\Provider;
use App\Models\ProviderLinkage;
use App\Models\ProviderVersion;
use App\Settings\CompanySettings;
use App\Support\ProviderPurchaseRules;
use Carbon\CarbonImmutable;
use Database\Seeders\CharacteristicOptionSeeder;
use Database\Seeders\ProcessSeeder;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed([ProcessSeeder::class, CharacteristicOptionSeeder::class]);

    $colombia = GeographicLocation::factory()->create(['code' => 'CO', 'name' => 'Colombia']);
    $department = GeographicLocation::factory()->department($colombia)->create(['code' => '23', 'name' => 'Córdoba']);
    $this->municipality = GeographicLocation::factory()->city($department)->create(['code' => '23001', 'name' => 'Montería']);
});

/** Proveedor básico con vinculación administrativa activa. */
function basicProvider(array $attributes = []): ProviderLinkage
{
    $provider = Provider::factory()->create(['risk_level' => null, 'email' => 'basico@example.com', ...$attributes]);

    return ProviderLinkage::factory()->scope(ProviderScope::Administrative)->active()->create(['provider_id' => $provider->id]);
}

it('registra un proveedor básico activo, sin FO-300-01', function (): void {
    Livewire::actingAs(userWith(['administrative-providers-read', 'administrative-providers-create']))
        ->test(ProviderBasicModal::class, ['scope' => 'administrative'])
        ->call('openCreate')
        ->set('form.identification', '900555444')
        ->set('form.name', 'PAPELERÍA DEL SINÚ SAS')
        ->set('form.email', 'ventas@papeleria.test')
        ->set('form.phone', '3001112233')
        ->set('form.address', 'Calle 10 # 5-20')
        ->set('form.department_id', (string) $this->municipality->parent_id)
        ->set('form.municipality_id', (string) $this->municipality->id)
        ->set('form.payment_condition', 'Contado')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('show', false);

    $provider = Provider::where('identification', '900555444')->with('openVersion')->firstOrFail();
    $linkage = $provider->linkages()->firstOrFail();

    expect($provider->versions()->count())->toBe(0)
        ->and($provider->formStatus())->toBe(ProviderFormStatus::None)
        ->and($provider->municipality_id)->toBe($this->municipality->id)
        ->and($linkage->status)->toBe(ProviderLinkageStatus::Active)
        ->and($linkage->payment_condition)->toBe('Contado');
});

it('no permite el registro básico en un proceso que exige el formato', function (): void {
    app(SaveProviderBasic::class)->execute(['identification' => '1', 'name' => 'X'], ['payment_condition' => null, 'payment_term' => null], ProviderScope::Commercial);
})->throws(ValidationException::class);

it('edita en el modal básico a un proveedor sin formato', function (): void {
    $linkage = basicProvider();

    Livewire::actingAs(userWith(['administrative-providers-read', 'administrative-providers-update']))
        ->test(ProviderFormModal::class, ['scope' => 'administrative'])
        ->call('openEdit', $linkage->id)
        ->assertDispatched('open-provider-basic-edit', linkageId: $linkage->id)
        ->assertSet('show', false);

    Livewire::actingAs(userWith(['administrative-providers-read', 'administrative-providers-update']))
        ->test(ProviderBasicModal::class, ['scope' => 'administrative'])
        ->call('openEdit', $linkage->id)
        ->assertSet('form.identityLocked', true)
        ->set('form.phone', '3209998877')
        ->set('form.department_id', (string) $this->municipality->parent_id)
        ->set('form.municipality_id', (string) $this->municipality->id)
        ->set('form.address', 'Nueva dirección')
        ->call('save')
        ->assertHasNoErrors();

    expect($linkage->provider->fresh()->phone)->toBe('3209998877');
});

it('diligencia el FO-300-01 de un proveedor básico y al aprobarlo queda vigente', function (): void {
    $linkage = basicProvider(['contact_name' => 'Ana Ventas']);

    Livewire::actingAs(userWith(['administrative-providers-read', 'administrative-providers-create']))
        ->test('administrative::pages.administrative-provider-show', ['linkage' => $linkage])
        ->assertSee('Diligenciar FO-300-01')
        ->call('startUpdate');

    $draft = $linkage->provider->openVersion()->with('contacts')->firstOrFail();
    expect($draft->number)->toBe(1)
        ->and($draft->request_type->value)->toBe('linkage')
        ->and($draft->email)->toBe('basico@example.com')
        ->and($draft->contacts->first()->name)->toBe('Ana Ventas');

    // El Borrador del formato reemplaza la edición básica mientras está en trámite.
    expect(userWith(['administrative-providers-update'])->can('updateBasic', $linkage->fresh()))->toBeFalse();

    $workflow = app(ProviderWorkflow::class);
    $draft->update(['status' => ProviderVersionStatus::Approval]);
    $workflow->approve($draft, tap(userWith(), fn ($user) => Process::where('name', 'Administrativo')->update(['director_id' => $user->id])), null);

    $provider = $linkage->provider->fresh(['openVersion']);
    expect($provider->formStatus())->toBe(ProviderFormStatus::Current)
        ->and($linkage->fresh()->status)->toBe(ProviderLinkageStatus::Active);
});

it('vincula de inmediato a Administrativo un proveedor que ya existe en Compras', function (): void {
    $version = ProviderVersion::factory()->approved()->create();
    ProviderLinkage::factory()->scope(ProviderScope::Commercial)->active()->create(['provider_id' => $version->provider_id]);

    $linkage = app(ProviderWorkflow::class)->link($version->provider, ProviderScope::Administrative);

    expect($linkage->status)->toBe(ProviderLinkageStatus::Active)
        ->and($version->provider->openVersion()->exists())->toBeFalse();
});

it('guarda el SMMLV por año en Datos de la empresa', function (): void {
    Livewire::actingAs(userWith(['company-settings-read', 'company-settings-update']))
        ->test('pages::company-settings')
        ->call('openEdit')
        ->assertSet('form.minimum_wages', [['year' => 2026, 'amount' => 1750905]])
        ->call('addMinimumWage')
        ->set('form.minimum_wages.0.amount', '1900000')
        ->call('save')
        ->assertHasNoErrors();

    expect(app(CompanySettings::class)->refresh()->minimum_wages)->toBe(['2026' => 1750905, '2027' => 1900000]);
});

describe('regla de compra (tope de 2 SMMLV)', function (): void {
    beforeEach(function (): void {
        $this->date = CarbonImmutable::parse('2026-10-20');
        $this->rules = app(ProviderPurchaseRules::class);
    });

    it('calcula el tope con el SMMLV del año', function (): void {
        expect($this->rules->threshold($this->date))->toBe(3501810);
    });

    it('permite comprar a un proveedor básico por debajo del tope', function (): void {
        $check = $this->rules->evaluate(basicProvider(), 1500000, 1000000, $this->date);

        expect($check->status)->toBe(ProviderPurchaseStatus::Allowed);
    });

    it('exige el formato cuando la orden supera el tope', function (): void {
        $check = $this->rules->evaluate(basicProvider(), 3600000, 0, $this->date);

        expect($check->status)->toBe(ProviderPurchaseStatus::FormRequired)
            ->and($check->status->allowsOrder())->toBeTrue();
    });

    it('exige el formato cuando el acumulado del mes supera el tope', function (): void {
        // Ejemplo del usuario: OC aprobada de 2.000.000 el 05/10 y OC en revisión de 2.500.000 el 20/10.
        $linkage = basicProvider();
        $check = $this->rules->evaluate($linkage, 2500000, 2000000, $this->date);

        expect($check->status)->toBe(ProviderPurchaseStatus::FormRequired)
            ->and($check->reason)->toContain('Acumulado');

        $this->rules->markFormRequired($linkage, $check->reason);
        expect($linkage->fresh()->form_required_at)->not->toBeNull();
    });

    it('no pone tope a un proveedor con formato vigente', function (): void {
        $version = ProviderVersion::factory()->approved()->create();
        $linkage = ProviderLinkage::factory()->scope(ProviderScope::Administrative)->active()->create(['provider_id' => $version->provider_id]);

        expect($this->rules->evaluate($linkage, 50000000, 0, $this->date)->status)->toBe(ProviderPurchaseStatus::Allowed);
    });

    it('bloquea las órdenes a un proveedor con el formato vencido', function (): void {
        $version = ProviderVersion::factory()->approved()->create();
        $version->provider->update(['next_update_on' => '2026-01-15']);
        $linkage = ProviderLinkage::factory()->scope(ProviderScope::Administrative)->active()->create(['provider_id' => $version->provider_id]);

        $check = $this->rules->evaluate($linkage, 100000, 0, $this->date);

        expect($check->status)->toBe(ProviderPurchaseStatus::Blocked)
            ->and($check->status->allowsOrder())->toBeFalse();
    });

    it('bloquea las órdenes a una vinculación inactiva', function (): void {
        $linkage = basicProvider();
        $linkage->update(['status' => ProviderLinkageStatus::Inactive]);

        expect($this->rules->evaluate($linkage, 100000, 0, $this->date)->status)->toBe(ProviderPurchaseStatus::Blocked);
    });

    it('exige configurar el SMMLV del año de la orden', function (): void {
        $this->rules->threshold(CarbonImmutable::parse('2030-01-10'));
    })->throws(RuntimeException::class);
});
