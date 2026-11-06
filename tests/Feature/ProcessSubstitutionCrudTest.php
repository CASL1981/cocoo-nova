<?php

use App\Livewire\Processes\ProcessSubstitutionFormModal;
use App\Models\Process;
use App\Models\ProcessSubstitution;
use App\Models\User;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

function substitutionModal(): Testable
{
    return Livewire::actingAs(userWith(['process-substitutions-read', 'process-substitutions-create', 'process-substitutions-update']))
        ->test(ProcessSubstitutionFormModal::class);
}

it('niega el acceso al listado sin permiso', function (): void {
    $this->actingAs(userWith())
        ->get(route('process-substitutions.index'))
        ->assertForbidden();
});

it('muestra el listado con permiso de lectura', function (): void {
    $substitution = ProcessSubstitution::factory()->permanent()->create();

    $this->actingAs(userWith('process-substitutions-read'))
        ->get(route('process-substitutions.index'))
        ->assertOk()
        ->assertSee($substitution->process->name)
        ->assertSee('Permanente');
});

it('registra un reemplazo con vigencia', function (): void {
    $process = Process::factory()->create(['director_id' => User::factory()->create()->id]);
    $substitute = User::factory()->create();

    substitutionModal()
        ->dispatch('open-process-substitution-create')
        ->set('form.process_id', (string) $process->id)
        ->call('selectUser', (string) $substitute->id)
        ->set('form.starts_on', '2026-11-01')
        ->set('form.ends_on', '2026-11-15')
        ->set('form.observations', 'Vacaciones del director')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('process-substitution-saved');

    $substitution = ProcessSubstitution::first();

    expect($substitution->substitute_id)->toBe($substitute->id)
        ->and($substitution->isPermanent())->toBeFalse();
});

it('registra una asignación permanente sin fecha de fin', function (): void {
    $process = Process::factory()->create();
    $substitute = User::factory()->create();

    substitutionModal()
        ->dispatch('open-process-substitution-create')
        ->set('form.process_id', (string) $process->id)
        ->set('form.substitute_id', (string) $substitute->id)
        ->set('form.starts_on', '2026-11-01')
        ->call('save')
        ->assertHasNoErrors();

    expect(ProcessSubstitution::first()->isPermanent())->toBeTrue();
});

it('no permite que el director sea su propio reemplazo', function (): void {
    $director = User::factory()->create();
    $process = Process::factory()->create(['director_id' => $director->id]);

    substitutionModal()
        ->dispatch('open-process-substitution-create')
        ->set('form.process_id', (string) $process->id)
        ->set('form.substitute_id', (string) $director->id)
        ->set('form.starts_on', '2026-11-01')
        ->call('save')
        ->assertHasErrors(['form.substitute_id']);
});

it('rechaza un rango de fechas invertido', function (): void {
    substitutionModal()
        ->dispatch('open-process-substitution-create')
        ->set('form.process_id', (string) Process::factory()->create()->id)
        ->set('form.substitute_id', (string) User::factory()->create()->id)
        ->set('form.starts_on', '2026-11-15')
        ->set('form.ends_on', '2026-11-01')
        ->call('save')
        ->assertHasErrors(['form.ends_on']);
});

it('rechaza reemplazos cruzados del mismo usuario y proceso', function (): void {
    $existing = ProcessSubstitution::factory()->create(['starts_on' => '2026-11-01', 'ends_on' => '2026-11-15']);

    substitutionModal()
        ->dispatch('open-process-substitution-create')
        ->set('form.process_id', (string) $existing->process_id)
        ->set('form.substitute_id', (string) $existing->substitute_id)
        ->set('form.starts_on', '2026-11-10')
        ->call('save')
        ->assertHasErrors(['form.starts_on']);
});

it('permite editar un reemplazo sin chocar consigo mismo', function (): void {
    $substitution = ProcessSubstitution::factory()->create(['starts_on' => '2026-11-01', 'ends_on' => '2026-11-15']);

    substitutionModal()
        ->dispatch('open-process-substitution-edit', substitutionId: $substitution->id)
        ->set('form.ends_on', '2026-11-20')
        ->call('save')
        ->assertHasNoErrors();

    expect($substitution->fresh()->ends_on->toDateString())->toBe('2026-11-20');
});

it('identifica los reemplazos vigentes en una fecha', function (): void {
    $current = ProcessSubstitution::factory()->create(['starts_on' => now()->subDays(2), 'ends_on' => now()->addDays(2)]);
    $permanent = ProcessSubstitution::factory()->permanent()->create(['starts_on' => now()->subYear()]);
    ProcessSubstitution::factory()->create(['starts_on' => now()->subMonth(), 'ends_on' => now()->subDays(10)]);

    expect(ProcessSubstitution::activeOn()->pluck('id')->sort()->values()->all())
        ->toBe(collect([$current->id, $permanent->id])->sort()->values()->all());
});

it('lista usuarios activos en el campo Reemplazo sin necesidad de escribir', function (): void {
    $active = User::factory()->create(['name' => 'Wendy', 'lastname' => 'Puello']);
    $inactive = User::factory()->inactive()->create(['name' => 'Inactivo']);

    $results = substitutionModal()
        ->dispatch('open-process-substitution-create')
        ->get('userResults');

    expect($results)->toHaveKey($active->id)
        ->not->toHaveKey($inactive->id);
});
