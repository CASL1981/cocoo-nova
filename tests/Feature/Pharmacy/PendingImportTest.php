<?php

use App\Actions\Imports\ImportRows;
use App\Models\Characteristic;
use App\Models\CharacteristicOption;
use App\Models\ServicePoint;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Modules\Pharmacy\App\Enums\PendingStatus;
use Modules\Pharmacy\App\Enums\PendingType;
use Modules\Pharmacy\App\Imports\PendingImporter;
use Modules\Pharmacy\App\Models\Pending;

/** Archivo CSV de pendientes con los encabezados de la plantilla. */
function pendingsCsv(array $lines): UploadedFile
{
    $header = 'type,category,destination,reason,manager';

    return UploadedFile::fake()->createWithContent('pendientes.csv', implode("\n", [$header, ...$lines]));
}

/** Característica de categorías de pendientes con una opción activa. */
function seedImportPendingCategory(): void
{
    $characteristic = Characteristic::factory()->create(['code' => Pending::CATEGORY_CHARACTERISTIC_CODE]);
    CharacteristicOption::factory()->create(['characteristic_id' => $characteristic->id, 'name' => 'MEDICAMENTOS']);
}

function servicePointCode(User $user): string
{
    return $user->loadMissing('servicePoint')->servicePoint->code;
}

it('crea un pendiente por fila con el usuario como solicitante', function (): void {
    seedImportPendingCategory();
    $user = userWith(['pendings-read', 'pendings-import']);
    $code = servicePointCode($user);

    $result = app(ImportRows::class)->execute(new PendingImporter($user), pendingsCsv([
        "PD,medicamentos,{$code},Reposición,",
        "requisición,MEDICAMENTOS,{$code},,Jefe",
    ]));

    expect($result->errors)->toBe([]);
    expect($result->failed())->toBeFalse()
        ->and($result->created)->toBe(2)
        ->and($result->updated)->toBe(0);

    $pendings = Pending::orderBy('id')->get();
    expect($pendings->pluck('type')->all())->toBe([PendingType::from('PD'), PendingType::from('RQ')])
        ->and($pendings->pluck('category')->unique()->all())->toBe(['MEDICAMENTOS'])
        ->and($pendings->pluck('user_id')->unique()->all())->toBe([$user->id])
        ->and($pendings->pluck('destination_id')->unique()->all())->toBe([$user->service_point_id])
        ->and($pendings->pluck('status')->unique()->all())->toBe([PendingStatus::Active]);
});

it('rechaza destinos de otra sede si el usuario no es de Oficina Principal', function (): void {
    seedImportPendingCategory();
    $user = userWith(['pendings-read', 'pendings-import']);
    $other = ServicePoint::factory()->create();

    $result = app(ImportRows::class)->execute(new PendingImporter($user), pendingsCsv([
        "PD,MEDICAMENTOS,{$other->code},,",
    ]));

    expect($result->failed())->toBeTrue()
        ->and($result->errors[0])->toStartWith('Fila 2:');
    expect(Pending::count())->toBe(0);
});

it('permite a Oficina Principal importar para cualquier sede', function (): void {
    seedImportPendingCategory();
    $headquarters = ServicePoint::factory()->create(['code' => ServicePoint::HEADQUARTERS_CODE]);
    $user = User::factory()->create(['service_point_id' => $headquarters->id])->givePermissionTo(['pendings-read', 'pendings-import']);
    $other = ServicePoint::factory()->create();

    $result = app(ImportRows::class)->execute(new PendingImporter($user), pendingsCsv([
        "MI,MEDICAMENTOS,{$other->code},,",
    ]));

    expect($result->failed())->toBeFalse();
    expect(Pending::sole()->destination_id)->toBe($other->id);
});

it('reporta las columnas obligatorias ausentes del archivo', function (): void {
    $user = userWith(['pendings-read', 'pendings-import']);
    $file = UploadedFile::fake()->createWithContent('pendientes.csv', "reason\nSin tipo");

    $result = app(ImportRows::class)->execute(new PendingImporter($user), $file);

    expect($result->failed())->toBeTrue()
        ->and(implode(' ', $result->errors))->toContain('Tipo')->toContain('Categoría')->toContain('Destino');
});

it('importa desde el listado de pendientes', function (): void {
    seedImportPendingCategory();
    $user = userWith(['pendings-read', 'pendings-import']);

    Livewire::actingAs($user)
        ->test('pharmacy::pages.pending-index')
        ->call('openImport')
        ->set('importFile', pendingsCsv(['PD,MEDICAMENTOS,'.servicePointCode($user).',,']))
        ->call('import')
        ->assertHasNoErrors()
        ->assertSet('showImport', false);

    expect(Pending::count())->toBe(1);
});

it('exige el permiso pendings-import', function (): void {
    Livewire::actingAs(userWith(['pendings-read', 'pendings-create']))
        ->test('pharmacy::pages.pending-index')
        ->call('openImport')
        ->assertForbidden();
});
