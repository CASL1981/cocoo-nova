<?php

use App\Enums\Status;
use App\Livewire\Processes\ProcessFormModal;
use App\Models\Process;
use App\Models\User;
use Livewire\Livewire;

it('niega el acceso al listado sin permiso', function (): void {
    $this->actingAs(userWith())
        ->get(route('processes.index'))
        ->assertForbidden();
});

it('muestra el listado con permiso de lectura', function (): void {
    $process = Process::factory()->create(['name' => 'Logística y Operaciones']);

    $this->actingAs(userWith('processes-read'))
        ->get(route('processes.index'))
        ->assertOk()
        ->assertSee('Logística y Operaciones');
});

it('crea un proceso con su director', function (): void {
    $director = User::factory()->create(['name' => 'Laura', 'lastname' => 'Pérez']);

    Livewire::actingAs(userWith(['processes-read', 'processes-create']))
        ->test(ProcessFormModal::class)
        ->dispatch('open-process-create')
        ->set('form.name', 'Gerencia')
        ->set('userSearch', 'Laura')
        ->assertSee('Laura Pérez')
        ->call('selectUser', (string) $director->id)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('process-saved');

    $process = Process::where('name', 'Gerencia')->first();

    expect($process->director_id)->toBe($director->id)
        ->and($process->status)->toBe(Status::Active);
});

it('rechaza nombres duplicados y directores inactivos', function (): void {
    Process::factory()->create(['name' => 'Financiero']);
    $inactive = User::factory()->inactive()->create();

    Livewire::actingAs(userWith(['processes-read', 'processes-create']))
        ->test(ProcessFormModal::class)
        ->dispatch('open-process-create')
        ->set('form.name', 'Financiero')
        ->set('form.director_id', (string) $inactive->id)
        ->call('save')
        ->assertHasErrors(['form.name', 'form.director_id']);
});

it('edita un proceso y quita su director', function (): void {
    $process = Process::factory()->create(['director_id' => User::factory()->create()->id]);

    Livewire::actingAs(userWith(['processes-read', 'processes-update']))
        ->test(ProcessFormModal::class)
        ->dispatch('open-process-edit', processId: $process->id)
        ->assertSet('form.name', $process->name)
        ->call('clearDirector')
        ->call('save')
        ->assertHasNoErrors();

    expect($process->fresh()->director_id)->toBeNull();
});

it('no elimina procesos con usuarios asignados', function (): void {
    $withUsers = Process::factory()->create();
    User::factory()->create(['process_id' => $withUsers->id]);
    $empty = Process::factory()->create();

    Livewire::actingAs(userWith(['processes-read', 'processes-delete']))
        ->test('pages::process-index')
        ->set('selected', [(string) $withUsers->id, (string) $empty->id])
        ->call('deleteSelected');

    expect($withUsers->fresh()->trashed())->toBeFalse()
        ->and($empty->fresh()->trashed())->toBeTrue();
});

it('alterna el estado de los procesos seleccionados', function (): void {
    $process = Process::factory()->create();

    Livewire::actingAs(userWith(['processes-read', 'processes-toggle']))
        ->test('pages::process-index')
        ->set('selected', [(string) $process->id])
        ->call('toggleSelected');

    expect($process->fresh()->status)->toBe(Status::Inactive);
});

it('niega crear un proceso sin permiso', function (): void {
    Livewire::actingAs(userWith('processes-read'))
        ->test(ProcessFormModal::class)
        ->call('openCreate')
        ->assertForbidden();
});
