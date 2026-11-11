<?php

use App\Actions\Providers\ProviderWorkflow;
use App\Enums\ManagementApprovalMode;
use App\Enums\ProviderScope;
use App\Enums\ProviderVersionStatus;
use App\Models\Process;
use App\Models\ProcessSubstitution;
use App\Models\ProviderLinkage;
use App\Models\ProviderVersion;
use App\Models\User;
use App\Settings\CompanySettings;
use Database\Seeders\ProcessSeeder;
use Livewire\Livewire;
use Modules\HumanResources\App\Models\HrCharacteristic;
use Modules\HumanResources\App\Models\HrCharacteristicOption;

beforeEach(function (): void {
    $this->seed(ProcessSeeder::class);
    $this->positions = HrCharacteristic::factory()->positions()->create();
    $this->buyer = HrCharacteristicOption::factory()->create(['hr_characteristic_id' => $this->positions->id, 'name' => 'Coordinador de Compras']);
    $this->director = HrCharacteristicOption::factory()->create(['hr_characteristic_id' => $this->positions->id, 'name' => 'Director Comercial y de Mercadeo']);
});

/** Proveedor comercial con su versión en el estado indicado. */
function reviewProvider(ProviderVersionStatus $status, bool $pep = false): ProviderLinkage
{
    $factory = ProviderVersion::factory()->status($status);
    $version = ($pep ? $factory->pep() : $factory)->create();

    return ProviderLinkage::factory()->scope(ProviderScope::Commercial)->create(['provider_id' => $version->provider_id]);
}

/** Director del proceso con cargo. */
function reviewDirector(string $processName, ?HrCharacteristicOption $position = null): User
{
    $user = userWith('commercial-providers-read');
    $user->update(['position_id' => $position?->id]);
    Process::where('name', $processName)->update(['director_id' => $user->id]);

    return $user->refresh();
}

function reviewSettings(array $values): void
{
    $settings = app(CompanySettings::class);
    $settings->fill($values)->save();
}

it('guarda el cargo de quien verifica y de quien aprueba', function (): void {
    $linkage = reviewProvider(ProviderVersionStatus::Verification);
    $verifier = userWith(['commercial-providers-read', 'commercial-providers-process']);
    $verifier->update(['position_id' => $this->buyer->id]);

    Livewire::actingAs($verifier)
        ->test('shopping::pages.commercial-provider-show', ['linkage' => $linkage])
        ->call('verify');

    Livewire::actingAs(reviewDirector('Comercial', $this->director))
        ->test('shopping::pages.commercial-provider-show', ['linkage' => $linkage])
        ->call('approve')
        ->assertSee('Coordinador de Compras')
        ->assertSee('Director Comercial y de Mercadeo');

    $version = $linkage->provider->versions()->with('approvals')->first();

    expect($version->status)->toBe(ProviderVersionStatus::Approved)
        ->and($version->verified_position)->toBe('Coordinador de Compras')
        ->and($version->approvals->first()->user_position)->toBe('Director Comercial y de Mercadeo');

    // El cargo queda en la firma aunque la persona cambie de cargo después.
    $verifier->update(['position_id' => null]);
    expect($version->fresh()->verified_position)->toBe('Coordinador de Compras');
});

it('solo verifican los cargos configurados para el tipo de proveedor', function (): void {
    reviewSettings(['provider_verifier_positions' => ['administrative' => [], 'commercial' => [$this->buyer->id]]]);
    $linkage = reviewProvider(ProviderVersionStatus::Verification);

    $withoutPosition = userWith(['commercial-providers-read', 'commercial-providers-process']);

    Livewire::actingAs($withoutPosition)
        ->test('shopping::pages.commercial-provider-show', ['linkage' => $linkage])
        ->call('verify')
        ->assertForbidden();

    $withoutPosition->update(['position_id' => $this->buyer->id]);

    Livewire::actingAs($withoutPosition->refresh())
        ->test('shopping::pages.commercial-provider-show', ['linkage' => $linkage])
        ->call('verify');

    expect($linkage->provider->versions()->first()->status)->toBe(ProviderVersionStatus::Approval);
});

it('el oficial de cumplimiento puede observar un proveedor que no es PEP sin cambiar su estado', function (): void {
    $linkage = reviewProvider(ProviderVersionStatus::Approval);
    $officer = userWith('commercial-providers-read');
    reviewSettings(['compliance_officer_id' => $officer->id]);

    Livewire::actingAs($officer)
        ->test('shopping::pages.commercial-provider-show', ['linkage' => $linkage])
        ->call('addComplianceObservation')
        ->assertHasErrors(['reviewNotes'])
        ->set('reviewNotes', 'Revisar referencias comerciales antes de la primera compra.')
        ->call('addComplianceObservation')
        ->assertHasNoErrors();

    $version = $linkage->provider->versions()->first();

    expect($version->status)->toBe(ProviderVersionStatus::Approval)
        ->and($version->compliance_observations)->toBe('Revisar referencias comerciales antes de la primera compra.');

    Livewire::actingAs(userWith('commercial-providers-read'))
        ->test('shopping::pages.commercial-provider-show', ['linkage' => $linkage])
        ->call('addComplianceObservation')
        ->assertForbidden();
});

it('registra la firma física de Gerencia de un PEP sin bloquear la aprobación del director', function (): void {
    $linkage = reviewProvider(ProviderVersionStatus::Approval, pep: true);

    Livewire::actingAs(reviewDirector('Comercial'))
        ->test('shopping::pages.commercial-provider-show', ['linkage' => $linkage])
        ->call('approve');

    $version = $linkage->provider->versions()->first();
    expect($version->status)->toBe(ProviderVersionStatus::Approved);

    // Quien no tiene el permiso de procesar no registra la firma.
    Livewire::actingAs(userWith('commercial-providers-read'))
        ->test('shopping::pages.commercial-provider-show', ['linkage' => $linkage])
        ->call('openManagementApproval')
        ->assertForbidden();

    $clerk = userWith(['commercial-providers-read', 'commercial-providers-process']);
    $clerk->update(['position_id' => $this->buyer->id]);

    Livewire::actingAs($clerk->refresh())
        ->test('shopping::pages.commercial-provider-show', ['linkage' => $linkage])
        ->call('openManagementApproval')
        ->set('managementSignedOn', today()->subDay()->toDateString())
        ->set('reviewNotes', 'Firmado en reunión de Gerencia.')
        ->call('registerManagementApproval')
        ->assertHasNoErrors();

    $version->refresh();

    expect($version->management_in_system)->toBeFalse()
        ->and($version->management_signed_on->toDateString())->toBe(today()->subDay()->toDateString())
        ->and($version->management_position)->toBe('Coordinador de Compras');
});

it('con Gerencia en el sistema un PEP no queda aprobado hasta que Gerencia firma', function (): void {
    reviewSettings(['provider_pep_management_approval' => ManagementApprovalMode::System->value]);
    $linkage = reviewProvider(ProviderVersionStatus::Approval, pep: true);
    $version = $linkage->provider->versions()->first();

    app(ProviderWorkflow::class)->approve($version, reviewDirector('Comercial'), null);
    expect($version->fresh()->status)->toBe(ProviderVersionStatus::Approval);

    // El director Comercial no aprueba como Gerencia.
    Livewire::actingAs(Process::named('Comercial')->director)
        ->test('shopping::pages.commercial-provider-show', ['linkage' => $linkage])
        ->call('approveAsManagement')
        ->assertForbidden();

    Livewire::actingAs(reviewDirector('Gerencia'))
        ->test('shopping::pages.commercial-provider-show', ['linkage' => $linkage])
        ->call('approveAsManagement');

    $version->refresh();

    expect($version->status)->toBe(ProviderVersionStatus::Approved)
        ->and($version->management_in_system)->toBeTrue();
});

it('devolver a borrador descarta las firmas y sus cargos', function (): void {
    $linkage = reviewProvider(ProviderVersionStatus::Approval);
    $version = $linkage->provider->versions()->first();
    $version->update(['verified_position' => 'Coordinador de Compras', 'compliance_observations' => 'Observación']);

    app(ProviderWorkflow::class)->returnToDraft($version);

    expect($version->fresh()->verified_position)->toBeNull()
        ->and($version->fresh()->compliance_observations)->toBeNull();
});

it('avisa cuando el proceso que aprueba no tiene director y muestra quién firma cuando lo tiene', function (): void {
    $linkage = reviewProvider(ProviderVersionStatus::Approval);
    $viewer = userWith('commercial-providers-read');

    // Sin director: nadie ve Aprobar y la pantalla explica la causa.
    Livewire::actingAs($viewer)
        ->test('shopping::pages.commercial-provider-show', ['linkage' => $linkage])
        ->assertSee('no tiene director ni reemplazo vigente')
        ->assertDontSee('wire:click="approve"', false);

    // Con un reemplazo vigente, se muestra su nombre y el aviso desaparece.
    $substitute = userWith('commercial-providers-read');
    ProcessSubstitution::factory()->permanent()->create([
        'process_id' => Process::where('name', 'Comercial')->value('id'),
        'substitute_id' => $substitute->id,
        'starts_on' => today()->subDay()->toDateString(),
    ]);

    Livewire::actingAs($substitute)
        ->test('shopping::pages.commercial-provider-show', ['linkage' => $linkage])
        ->assertDontSee('no tiene director ni reemplazo vigente')
        ->assertSee($substitute->fullName())
        ->assertSee('wire:click="approve"', false);
});
