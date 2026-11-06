<?php

use App\Actions\Imports\ImportRows;
use App\Enums\GeographicLevel;
use App\Enums\Status;
use App\Imports\GeographicLocationImporter;
use App\Livewire\GeographicLocations\GeographicLocationFormModal;
use App\Models\GeographicLocation;
use App\Models\Provider;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->colombia = GeographicLocation::factory()->create(['code' => 'CO', 'name' => 'Colombia']);
    $this->cordoba = GeographicLocation::factory()->department($this->colombia)->create(['code' => '23', 'name' => 'Córdoba']);
});

/** Archivo CSV temporal con las filas dadas (la primera es el encabezado). */
function geographicCsv(array $rows): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'geo').'.csv';
    $handle = fopen($path, 'w');
    foreach ($rows as $row) {
        fputcsv($handle, $row, ',', '"', '');
    }
    fclose($handle);

    return new UploadedFile($path, 'ubicaciones.csv', 'text/csv', null, true);
}

it('muestra la pantalla solo con el permiso de lectura', function (): void {
    $this->actingAs(userWith('geographic-locations-read'))->get(route('geographic-locations.index'))
        ->assertOk()->assertSee('Ubicación geográfica')->assertSee('Córdoba');

    $this->actingAs(userWith())->get(route('geographic-locations.index'))->assertForbidden();
});

it('crea una ciudad dentro de un departamento', function (): void {
    Livewire::actingAs(actingAsAdmin())
        ->test(GeographicLocationFormModal::class)
        ->call('openCreate')
        ->set('form.level', 'city')
        ->set('form.parent_id', (string) $this->cordoba->id)
        ->set('form.code', '23001')
        ->set('form.name', 'Montería')
        ->set('form.population', '465999')
        ->call('save')
        ->assertHasNoErrors();

    $city = GeographicLocation::where('code', '23001')->firstOrFail();

    expect($city->level)->toBe(GeographicLevel::City)
        ->and($city->parent_id)->toBe($this->cordoba->id)
        ->and($city->population)->toBe(465999)
        ->and($city->parentPath())->toBe('Córdoba · Colombia');
});

it('exige que el padre sea del nivel correcto', function (): void {
    // Una ciudad no puede colgar de un país.
    Livewire::actingAs(actingAsAdmin())
        ->test(GeographicLocationFormModal::class)
        ->call('openCreate')
        ->set('form.level', 'city')
        ->set('form.parent_id', (string) $this->colombia->id)
        ->set('form.code', '99001')
        ->set('form.name', 'Inválida')
        ->call('save')
        ->assertHasErrors(['form.parent_id']);

    // El código no se repite y se guarda en mayúsculas.
    Livewire::actingAs(actingAsAdmin())
        ->test(GeographicLocationFormModal::class)
        ->call('openCreate')
        ->set('form.level', 'country')
        ->set('form.code', 'co')
        ->set('form.name', 'Colombia 2')
        ->call('save')
        ->assertHasErrors(['form.code']);
});

it('no cambia el código ni el padre de una ubicación en uso, pero sí su nombre', function (): void {
    $city = GeographicLocation::factory()->city($this->cordoba)->create(['code' => '23001', 'name' => 'Monteria']);
    Provider::factory()->create(['municipality_id' => $city->id]);
    $sucre = GeographicLocation::factory()->department($this->colombia)->create(['code' => '70', 'name' => 'Sucre']);

    Livewire::actingAs(actingAsAdmin())
        ->test(GeographicLocationFormModal::class)
        ->call('openEdit', $city->id)
        ->assertSet('form.lockReason', 'ya está en uso en proveedores')
        ->set('form.parent_id', (string) $sucre->id)
        ->set('form.code', '70001')
        ->call('save')
        ->assertHasErrors(['form.code', 'form.parent_id']);

    Livewire::actingAs(actingAsAdmin())
        ->test(GeographicLocationFormModal::class)
        ->call('openEdit', $city->id)
        ->set('form.name', 'Montería')
        ->call('save')
        ->assertHasNoErrors();

    expect($city->refresh()->name)->toBe('Montería')->and($city->code)->toBe('23001');
});

it('no elimina una ubicación en uso o con hijas, y no restaura una cuyo padre está eliminado', function (): void {
    $city = GeographicLocation::factory()->city($this->cordoba)->create();
    Provider::factory()->create(['municipality_id' => $city->id]);
    $free = GeographicLocation::factory()->city($this->cordoba)->create();

    Livewire::actingAs(actingAsAdmin())
        ->test('pages::geographic-location-index')
        ->set('selected', [(string) $city->id, (string) $this->cordoba->id, (string) $free->id])
        ->call('deleteSelected');

    expect($city->refresh()->trashed())->toBeFalse()
        ->and($this->cordoba->refresh()->trashed())->toBeFalse()
        ->and($free->refresh()->trashed())->toBeTrue();

    // Un departamento sin hijas activas se elimina; su ciudad eliminada no se restaura sin él.
    $department = GeographicLocation::factory()->department($this->colombia)->create();
    $orphan = GeographicLocation::factory()->city($department)->create();
    $orphan->delete();
    $department->delete();

    Livewire::actingAs(actingAsAdmin())
        ->test('pages::geographic-location-index')
        ->set('selected', [(string) $orphan->id])
        ->call('restoreSelected');

    expect($orphan->refresh()->trashed())->toBeTrue();
});

it('ofrece en los formularios solo las ubicaciones activas, más la ya guardada', function (): void {
    $active = GeographicLocation::factory()->city($this->cordoba)->create(['name' => 'Montería']);
    $inactive = GeographicLocation::factory()->city($this->cordoba)->create(['name' => 'Cereté', 'status' => Status::Inactive]);

    expect(GeographicLocation::childOptions(GeographicLevel::City, $this->cordoba->id))->toBe([$active->id => 'Montería'])
        ->and(GeographicLocation::childOptions(GeographicLevel::City, $this->cordoba->id, $inactive->id))->toHaveKey($inactive->id)
        ->and(GeographicLocation::childOptions(GeographicLevel::City, null))->toBe([]);
});

it('filtra por la ubicación a la que pertenece, incluido el país', function (): void {
    GeographicLocation::factory()->city($this->cordoba)->create(['name' => 'Montería']);
    $australia = GeographicLocation::factory()->create(['code' => 'AU', 'name' => 'Australia']);
    $state = GeographicLocation::factory()->department($australia)->create(['code' => 'AU-NSW', 'name' => 'Nueva Gales del Sur']);
    GeographicLocation::factory()->city($state)->create(['code' => 'AU-SYD', 'name' => 'Sídney']);

    Livewire::actingAs(actingAsAdmin())
        ->test('pages::geographic-location-index')
        ->set('filterParent', 'Colombia')
        ->assertSee('Montería')->assertDontSee('Sídney')
        ->set('filterParent', '')
        ->set('sortBy', 'parent')
        ->assertSee('Sídney');
});

it('importa ubicaciones nuevas y actualiza la población de las existentes', function (): void {
    $city = GeographicLocation::factory()->city($this->cordoba)->create(['code' => '23001', 'name' => 'Montería']);

    $result = app(ImportRows::class)->execute(new GeographicLocationImporter, geographicCsv([
        ['level', 'code', 'name', 'parent_code', 'population', 'status'],
        ['Ciudad', '23162', 'Cereté', '23', '53915', ''],
        ['', '23001', 'Montería', '', '465999', 'Inactivo'],
    ]));

    expect($result->errors)->toBe([])->and($result->failed())->toBeFalse()
        ->and($result->created)->toBe(1)
        ->and($result->updated)->toBe(1)
        ->and(GeographicLocation::where('code', '23162')->value('parent_id'))->toBe($this->cordoba->id)
        ->and($city->refresh()->population)->toBe(465999)
        ->and($city->status)->toBe(Status::Inactive)
        ->and($city->parent_id)->toBe($this->cordoba->id);
});

it('rechaza filas de importación incoherentes sin guardar nada', function (): void {
    $result = app(ImportRows::class)->execute(new GeographicLocationImporter, geographicCsv([
        ['level', 'code', 'name', 'parent_code'],
        ['Ciudad', '23162', 'Cereté', 'CO'],
        ['Departamento', '70', 'Sucre', 'XX'],
        ['', '99', 'Sin nivel', ''],
    ]));

    expect($result->failed())->toBeTrue()
        ->and(implode(' ', $result->errors))->toContain('Fila 2')->toContain('debe pertenecer a un departamento')
        ->toContain('No existe la ubicación padre')->toContain('requiere nivel y nombre')
        ->and(GeographicLocation::whereIn('code', ['23162', '70', '99'])->exists())->toBeFalse();
});
