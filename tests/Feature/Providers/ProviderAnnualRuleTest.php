<?php

use App\Actions\Providers\ProviderWorkflow;
use App\Enums\ProviderFormStatus;
use App\Enums\ProviderLinkageStatus;
use App\Enums\ProviderScope;
use App\Enums\ProviderVersionStatus;
use App\Models\Process;
use App\Models\ProviderLegacyUpdate;
use App\Models\ProviderLinkage;
use App\Models\ProviderVersion;
use Database\Seeders\CharacteristicOptionSeeder;
use Database\Seeders\ProcessSeeder;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed([ProcessSeeder::class, CharacteristicOptionSeeder::class]);
});

/** Versión rechazada del año en curso con su vinculación comercial rechazada. */
function rejectedThisYear(): ProviderLinkage
{
    $version = ProviderVersion::factory()->status(ProviderVersionStatus::Rejected)->create([
        'rejected_at' => now(), 'rejection_reason' => 'El RUT no corresponde al proveedor.',
    ]);

    return ProviderLinkage::factory()->scope(ProviderScope::Commercial)->create([
        'provider_id' => $version->provider_id, 'status' => ProviderLinkageStatus::Rejected,
    ]);
}

it('no abre una segunda solicitud del FO-300-01 en el mismo año', function (): void {
    $version = ProviderVersion::factory()->approved()->create();
    $linkage = ProviderLinkage::factory()->scope(ProviderScope::Commercial)->active()->create(['provider_id' => $version->provider_id]);

    expect(userWith(['commercial-providers-create'])->can('startUpdate', $linkage))->toBeFalse();

    app(ProviderWorkflow::class)->startUpdate($version->provider);
})->throws(ValidationException::class, 'solo se diligencia uno por año');

it('abre la actualización cuando el formato vigente es de un año anterior', function (): void {
    $version = ProviderVersion::factory()->approved()->create(['created_at' => now()->subYear()]);
    $linkage = ProviderLinkage::factory()->scope(ProviderScope::Commercial)->active()->create(['provider_id' => $version->provider_id]);

    expect(userWith(['commercial-providers-create'])->can('startUpdate', $linkage))->toBeTrue();
});

it('corrige el rechazado con permiso de reversar: vuelve a Borrador y la vinculación queda pendiente', function (): void {
    $linkage = rejectedThisYear();
    $version = $linkage->provider->versions()->firstOrFail();

    // Sin reversar no se corrige, y la regla anual impide abrir otra solicitud.
    Livewire::actingAs(userWith(['commercial-providers-read', 'commercial-providers-create', 'commercial-providers-update']))
        ->test('shopping::pages.commercial-provider-show', ['linkage' => $linkage])
        ->assertSee('El RUT no corresponde al proveedor.')
        ->assertDontSeeHtml('$wire.correctRejected()')
        ->call('correctRejected')
        ->assertForbidden();

    Livewire::actingAs(userWith(['commercial-providers-read', 'commercial-providers-reverse']))
        ->test('shopping::pages.commercial-provider-show', ['linkage' => $linkage])
        ->assertSeeHtml('$wire.correctRejected()')
        ->call('correctRejected');

    $version->refresh();
    expect($version->status)->toBe(ProviderVersionStatus::Draft)
        ->and($version->number)->toBe(1)
        ->and($version->rejection_reason)->toBe('El RUT no corresponde al proveedor.')
        ->and($linkage->fresh()->status)->toBe(ProviderLinkageStatus::Pending)
        ->and($linkage->provider->versions()->count())->toBe(1);
});

it('la corrección vuelve a pedir la firma del proceso rechazado', function (): void {
    $linkage = rejectedThisYear();
    $version = $linkage->provider->versions()->firstOrFail();
    $workflow = app(ProviderWorkflow::class);

    $workflow->correctRejected($version);
    $version->refresh()->update(['status' => ProviderVersionStatus::Approval]);

    $director = userWith();
    Process::where('name', 'Comercial')->update(['director_id' => $director->id]);
    $workflow->approve($version, $director, null);

    expect($version->fresh()->status)->toBe(ProviderVersionStatus::Approved)
        ->and($linkage->fresh()->status)->toBe(ProviderLinkageStatus::Active);
});

it('vincula otro proceso en el mismo año sobre el formato vigente, sin abrir otra versión', function (): void {
    $version = ProviderVersion::factory()->approved()->create();
    ProviderLinkage::factory()->scope(ProviderScope::Administrative)->active()->create(['provider_id' => $version->provider_id]);
    $nextUpdate = $version->provider->fresh()->next_update_on;

    $commercial = app(ProviderWorkflow::class)->link($version->provider, ProviderScope::Commercial);

    expect($commercial->status)->toBe(ProviderLinkageStatus::Pending)
        ->and($version->provider->versions()->count())->toBe(1);

    $director = userWith(['commercial-providers-read']);
    Process::where('name', 'Comercial')->update(['director_id' => $director->id]);

    Livewire::actingAs($director)
        ->test('shopping::pages.commercial-provider-show', ['linkage' => $commercial])
        ->assertSee('pendiente (director de Comercial)')
        ->call('approve')
        ->assertHasNoErrors();

    expect($commercial->fresh()->status)->toBe(ProviderLinkageStatus::Active)
        ->and($version->fresh()->status)->toBe(ProviderVersionStatus::Approved)
        ->and($version->provider->fresh()->next_update_on?->toDateString())->toBe($nextUpdate?->toDateString());
});

it('muestra los años del formato y consulta la versión de otro año en solo lectura', function (): void {
    $old = ProviderVersion::factory()->approved()->create(['created_at' => now()->subYear()]);
    $draft = ProviderVersion::factory()->status(ProviderVersionStatus::Draft)->create(['provider_id' => $old->provider_id, 'number' => 2]);
    ProviderLegacyUpdate::factory()->create(['provider_id' => $old->provider_id, 'year' => now()->subYears(3)->year]);
    $linkage = ProviderLinkage::factory()->scope(ProviderScope::Commercial)->active()->create(['provider_id' => $old->provider_id]);
    $foreign = ProviderVersion::factory()->create();

    $component = Livewire::actingAs(userWith(['commercial-providers-read', 'commercial-providers-update']))
        ->test('shopping::pages.commercial-provider-show', ['linkage' => $linkage]);

    $years = $component->get('years');
    expect(array_column($years, 'year'))->toBe([now()->year, now()->subYear()->year, now()->subYears(3)->year])
        ->and($years[2]['version'])->toBeNull()
        ->and($component->get('canEditDraft'))->toBeTrue();

    $component->call('selectVersion', $old->id)
        ->assertSet('selectedVersionId', $old->id)
        ->assertSee('Estás consultando el FO-300-01')
        ->assertDontSee('Editar borrador');
    expect($component->get('canEditDraft'))->toBeFalse()
        ->and($component->get('version')->id)->toBe($old->id);

    // Una versión de otro proveedor no se puede consultar desde esta vinculación.
    $component->call('selectVersion', $foreign->id)->assertSet('selectedVersionId', null);
    expect($component->get('version')->id)->toBe($draft->id);
});

it('ordena el listado por la situación del FO-300-01', function (): void {
    $current = ProviderVersion::factory()->approved()->create();
    $basic = ProviderLinkage::factory()->scope(ProviderScope::Administrative)->active()->create();
    ProviderLinkage::factory()->scope(ProviderScope::Administrative)->active()->create(['provider_id' => $current->provider_id]);
    $basic->provider->update(['current_version_id' => null, 'next_update_on' => null]);

    $component = Livewire::actingAs(userWith(['administrative-providers-read']))
        ->test('administrative::pages.administrative-provider-index')
        ->call('sort', 'open_version');

    expect($component->get('linkages')->pluck('provider_id')->all())->toBe([$basic->provider_id, $current->provider_id]);

    $component->call('sort', 'open_version');
    expect($component->get('linkages')->pluck('provider_id')->all())->toBe([$current->provider_id, $basic->provider_id]);
});

it('filtra el listado por la situación del FO-300-01', function (): void {
    $administrative = fn (int $providerId) => ProviderLinkage::factory()->scope(ProviderScope::Administrative)->active()
        ->create(['provider_id' => $providerId]);

    $basic = ProviderLinkage::factory()->scope(ProviderScope::Administrative)->active()->create();
    $basic->provider->update(['current_version_id' => null, 'next_update_on' => null]);
    $inProgress = ProviderVersion::factory()->status(ProviderVersionStatus::Verification)->create();
    $administrative($inProgress->provider_id);
    $current = ProviderVersion::factory()->approved()->create();
    $administrative($current->provider_id);
    $expired = ProviderVersion::factory()->approved()->create();
    $administrative($expired->provider_id);
    $expired->provider->update(['next_update_on' => today()]);

    $component = Livewire::actingAs(userWith(['administrative-providers-read']))
        ->test('administrative::pages.administrative-provider-index');

    foreach ([
        ProviderFormStatus::None->value => $basic->provider_id,
        ProviderFormStatus::InProgress->value => $inProgress->provider_id,
        ProviderFormStatus::Current->value => $current->provider_id,
        ProviderFormStatus::Expired->value => $expired->provider_id,
    ] as $status => $providerId) {
        expect($component->set('filterFormStatus', $status)->get('linkages')->pluck('provider_id')->all())
            ->toBe([$providerId]);
    }

    // Un valor fuera del enum no filtra.
    expect($component->set('filterFormStatus', 'otro')->get('linkages'))->toHaveCount(4);
});
