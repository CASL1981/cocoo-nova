<?php

use App\Enums\ProviderContactRole;
use App\Enums\ProviderFormStatus;
use App\Enums\ProviderLinkageStatus;
use App\Enums\ProviderScope;
use App\Enums\ProviderVersionStatus;
use App\Models\Provider;
use App\Models\ProviderLinkage;
use App\Models\ProviderVersion;
use App\Models\ProviderVersionContact;
use Database\Seeders\ProviderHistorySeeder;
use Database\Seeders\ProviderInitialLoadSeeder;
use Database\Seeders\ReferenceCatalogSeeder;

beforeEach(function (): void {
    $this->seed([ReferenceCatalogSeeder::class, ProviderHistorySeeder::class]);
});

it('deja vigente el FO-300-01 2026 con contactos y accionistas del archivo revisado', function (): void {
    $this->seed(ProviderInitialLoadSeeder::class);

    $provider = Provider::with(['currentVersion.contacts', 'currentVersion.shareholders', 'linkages'])
        ->where('identification', '900808189')->firstOrFail();
    $version = $provider->currentVersion;

    expect($version)->not->toBeNull()
        ->and($version->created_at->year)->toBe(2026)
        ->and($version->status)->toBe(ProviderVersionStatus::Approved)
        ->and($provider->formStatus())->toBe(ProviderFormStatus::Current)
        ->and($provider->next_update_on->toDateString())->toBe($version->approved_at->addMonths(12)->toDateString())
        ->and($version->contacts->pluck('role')->all())->toContain(ProviderContactRole::SalesRepresentative)
        ->and($version->shareholders)->toHaveCount(4)
        ->and((float) $version->shareholders->sum('ownership_percentage'))->toBe(100.0)
        // Las versiones 2024 y 2025 del histórico se conservan.
        ->and($provider->versions()->count())->toBe(3);
});

it('crea los proveedores nuevos y aplica el estado del archivo', function (): void {
    $this->seed(ProviderInitialLoadSeeder::class);

    $sgs = Provider::with('linkages')->where('identification', '860049921')->firstOrFail();
    $google = ProviderLinkage::whereHas('provider', fn ($query) => $query->where('identification', '770493581'))->firstOrFail();
    $inactive = ProviderLinkage::whereHas('provider', fn ($query) => $query->where('identification', '901049499'))->firstOrFail();

    expect($sgs->current_version_id)->not->toBeNull()
        ->and($sgs->linkages->first()->scope)->toBe(ProviderScope::Administrative)
        // "En evaluación" queda Activa (decisión del usuario).
        ->and($google->status)->toBe(ProviderLinkageStatus::Active)
        ->and($inactive->status)->toBe(ProviderLinkageStatus::Inactive);
});

it('no repite la carga al ejecutarse de nuevo', function (): void {
    $this->seed(ProviderInitialLoadSeeder::class);
    $versions = ProviderVersion::count();
    $providers = Provider::count();

    $this->seed(ProviderInitialLoadSeeder::class);

    expect(ProviderVersion::count())->toBe($versions)
        ->and(Provider::count())->toBe($providers)
        ->and(ProviderVersion::whereYear('created_at', 2026)->count())->toBe(39);
});

it('valida los accionistas con las reglas del formato y los reemplaza al volver a cargar', function (): void {
    $this->seed(ProviderInitialLoadSeeder::class);
    $this->seed(ProviderInitialLoadSeeder::class);

    $shareholders = fn (string $identification) => Provider::with('currentVersion.shareholders')
        ->where('identification', $identification)->firstOrFail()->currentVersion->shareholders;

    // 830020915: la fila con 0 % se omite (el formato solo registra 5 % o más).
    expect($shareholders('830020915')->pluck('identification')->all())->toEqualCanonicalizing(['A80192727', '860009195'])
        // Documentos extranjeros: solo letras y dígitos.
        ->and($shareholders('860049921')->first()->identification)->toBe('CHE105923438')
        // Sin duplicar al ejecutarse dos veces.
        ->and($shareholders('900808189'))->toHaveCount(4);
});

it('carga el cargo de cada contacto y deja la última fila cuando el rol se repite', function (): void {
    $this->seed(ProviderInitialLoadSeeder::class);
    $this->seed(ProviderInitialLoadSeeder::class);

    $contacts = fn (string $identification) => Provider::with('currentVersion.contacts')
        ->where('identification', $identification)->firstOrFail()->currentVersion->contacts->keyBy(fn ($contact) => $contact->role->value);

    $bureau = $contacts('830055049');
    $sgs = $contacts('860049921');

    // Rol repetido: gana la fila revisada (correo corregido) y el cargo sale de la novedad.
    expect($bureau['sales_representative']->email)->toBe('luis-carlos.martinez@bureauveritas.com')
        ->and($bureau['sales_representative']->job_title)->toBe('COORDINADOR NUEVOS NEGOCIOS')
        // Columna cargo, sin la anotación "- NO SE DETALLA…".
        ->and($sgs['sales_representative']->job_title)->toBe('JEFE COMERCIAL SA CAM')
        ->and($sgs['billing']->job_title)->toBe('SUPERVISOR DE OPERACIONES')
        // Sin cargo en el archivo: texto por defecto.
        ->and($contacts('900808189')['sales_representative']->job_title)->toBe(ProviderVersionContact::DEFAULT_JOB_TITLE);
});
