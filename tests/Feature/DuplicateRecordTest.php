<?php

use App\Enums\ServicePointStatus;
use App\Enums\Status;
use App\Livewire\Characteristics\CharacteristicList;
use App\Livewire\Characteristics\CharacteristicOptionList;
use App\Models\Characteristic;
use App\Models\CharacteristicOption;
use App\Models\ServicePoint;
use Livewire\Livewire;

it('duplica una sede con código y abreviatura con sufijo COPIA, inactiva', function (): void {
    $source = ServicePoint::factory()->create(['code' => '501', 'abbreviation' => 'NORTE', 'status' => ServicePointStatus::Active]);
    $user = userWith(['service-points-read', 'service-points-create']);

    Livewire::actingAs($user)
        ->test('pages::service-point-index')
        ->call('duplicate', $source->id)
        ->assertDispatched('toast');

    $copy = ServicePoint::query()->where('code', '501-COPIA')->sole();
    expect($copy->abbreviation)->toBe('NORTE-COPIA')
        ->and($copy->name)->toBe($source->name)
        ->and($copy->status)->toBe(ServicePointStatus::Inactive)
        ->and($copy->created_by)->toBe($user->id);
});

it('numera la copia cuando el sufijo ya existe, incluso en eliminados, y respeta el largo', function (): void {
    $source = ServicePoint::factory()->create(['code' => '12345678901234567890', 'abbreviation' => 'SUR']);
    ServicePoint::factory()->create(['code' => '12345678901234-COPIA', 'abbreviation' => 'SUR-COPIA'])->delete();

    Livewire::actingAs(actingAsAdmin())
        ->test('pages::service-point-index')
        ->call('duplicate', $source->id);

    $copy = ServicePoint::query()->latest('id')->first();
    expect($copy->code)->toBe('123456789012-COPIA-2')
        ->and($copy->abbreviation)->toBe('SUR-COPIA-2');
});

it('niega duplicar sin permiso de crear', function (): void {
    $source = ServicePoint::factory()->create();

    Livewire::actingAs(userWith('service-points-read'))
        ->test('pages::service-point-index')
        ->call('duplicate', $source->id)
        ->assertForbidden();

    expect(ServicePoint::query()->where('code', 'like', '%-COPIA')->exists())->toBeFalse();
});

it('duplica una característica sin sus opciones y deja seleccionada la copia', function (): void {
    $source = Characteristic::factory()->create(['code' => 'eps', 'status' => Status::Active]);
    CharacteristicOption::factory()->create(['characteristic_id' => $source->id]);

    $list = Livewire::actingAs(actingAsAdmin())
        ->test(CharacteristicList::class)
        ->set('selected', [(string) $source->id])
        ->call('duplicateSelected');

    $copy = Characteristic::query()->where('code', 'eps_copia')->sole();
    $list->assertSet('selected', [(string) $copy->id]);
    expect($copy->status)->toBe(Status::Inactive)
        ->and($copy->options()->count())->toBe(0);
});

it('duplica una opción con nombre único dentro de su característica', function (): void {
    $characteristic = Characteristic::factory()->create();
    $option = CharacteristicOption::factory()->create(['characteristic_id' => $characteristic->id, 'name' => 'CAPITADO']);

    Livewire::actingAs(actingAsAdmin())
        ->test(CharacteristicOptionList::class)
        ->call('setCharacteristic', $characteristic->id)
        ->set('selected', [(string) $option->id])
        ->call('duplicateSelected');

    $copy = CharacteristicOption::query()->where('name', 'CAPITADO-COPIA')->sole();
    expect($copy->characteristic_id)->toBe($characteristic->id)
        ->and($copy->status)->toBe(Status::Inactive);
});

it('no duplica opciones de una característica eliminada', function (): void {
    $characteristic = Characteristic::factory()->create();
    $option = CharacteristicOption::factory()->create(['characteristic_id' => $characteristic->id]);
    $characteristic->delete();

    Livewire::actingAs(actingAsAdmin())
        ->test(CharacteristicOptionList::class)
        ->call('setCharacteristic', $characteristic->id)
        ->set('selected', [(string) $option->id])
        ->call('duplicateSelected')
        ->assertForbidden();

    expect(CharacteristicOption::query()->count())->toBe(1);
});
