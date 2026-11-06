<?php

use App\Enums\IdentityDocumentType;
use App\Enums\PersonType;
use App\Enums\ProviderContactRole;
use App\Enums\ProviderFormStatus;
use App\Enums\ProviderLinkageStatus;
use App\Enums\ProviderScope;
use App\Enums\ProviderVersionStatus;
use App\Models\Provider;
use App\Models\ProviderLinkage;
use App\Models\ProviderVersion;
use Database\Seeders\ProviderHistorySeeder;
use Database\Seeders\ReferenceCatalogSeeder;

beforeEach(function (): void {
    $this->seed(ReferenceCatalogSeeder::class);
});

it('carga los formatos 2024 y 2025 como histórico sin volver vigente el formato', function (): void {
    // Proveedor básico que ya existía (sistema de requisiciones): conserva sus datos básicos.
    $existing = Provider::factory()->create([
        'identification' => '901419883', 'name' => 'GRUPO DORISA', 'document_type' => IdentityDocumentType::Nit,
        'person_type' => PersonType::Legal, 'email' => 'anterior@doriautos.test', 'current_version_id' => null, 'risk_level' => null, 'next_update_on' => null,
    ]);
    ProviderLinkage::factory()->scope(ProviderScope::Administrative)->active()->create(['provider_id' => $existing->id]);

    $this->seed(ProviderHistorySeeder::class);

    $existing->refresh()->load('openVersion');
    $versions = $existing->versions()->with('contacts')->orderBy('number')->get();

    expect($versions->map(fn (ProviderVersion $version) => $version->created_at->year)->all())->toBe([2024, 2025])
        ->and($versions->every(fn (ProviderVersion $version) => $version->status === ProviderVersionStatus::Approved))->toBeTrue()
        ->and($existing->email)->toBe('anterior@doriautos.test')
        ->and($existing->formStatus())->toBe(ProviderFormStatus::None);

    $latest = $versions->last();
    expect($latest->legal_representative_name)->toBe('JORGE ELIECER DORIA CORRALES')
        ->and($latest->legal_representative_identification)->toBe('78017786')
        ->and($latest->email)->toBe('jdoria@doriautos.com')
        ->and($latest->contacts->firstWhere('role', ProviderContactRole::SalesRepresentative)?->email)->toBe('coord.administrativo@doriautos.com');

    // Proveedor que no existía: se crea básico con vinculación administrativa; la fila corrida de 2024 se realinea.
    $created = Provider::where('identification', '78706393')->with('linkages')->firstOrFail();
    $first = $created->versions()->orderBy('number')->firstOrFail();

    expect($created->person_type)->toBe(PersonType::Natural)
        ->and($created->current_version_id)->toBeNull()
        ->and($created->linkages->first()->status)->toBe(ProviderLinkageStatus::Active)
        ->and($first->address)->toBe('CRA 14 N°36-67')
        ->and($first->legal_representative_name)->toBeNull();
});

it('no duplica los años ya cargados al ejecutarse de nuevo', function (): void {
    $this->seed(ProviderHistorySeeder::class);
    $versions = ProviderVersion::count();
    $providers = Provider::count();

    $this->seed(ProviderHistorySeeder::class);

    expect(ProviderVersion::count())->toBe($versions)
        ->and(Provider::count())->toBe($providers)
        ->and($versions)->toBeGreaterThan(100);
});
