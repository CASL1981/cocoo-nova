<?php

use App\Livewire\Audit\UserstampsModal;
use App\Models\User;
use Livewire\Livewire;

it('muestra la información de auditoría de un registro sin ediciones ni eliminación', function () {
    $target = User::factory()->create();

    Livewire::actingAs(actingAsAdmin())
        ->test(UserstampsModal::class)
        ->call('open', User::class, $target->id)
        ->assertSet('show', true)
        ->assertSet('wasEdited', false)
        ->assertSet('wasDeleted', false);
});

it('rechaza clases que no son modelos Eloquent', function () {
    Livewire::actingAs(actingAsAdmin())
        ->test(UserstampsModal::class)
        ->call('open', \stdClass::class, 1)
        ->assertForbidden();
});
