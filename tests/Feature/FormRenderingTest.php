<?php

use Livewire\Livewire;
use Modules\Pharmacy\App\Models\Pending;

/*
 * Los modales y páginas de formulario se renderizan con los componentes compartidos
 * (<x-modal>, <x-forms.*>): una vista mal compilada rompe toda la pantalla.
 */

it('renderiza los listados con sus modales de formulario', function (string $route): void {
    $this->actingAs(actingAsAdmin())
        ->get(route($route))
        ->assertOk()
        ->assertSee('app-modal', false);
})->with([
    'users.index', 'roles.index', 'service-points.index', 'characteristics.index',
    'products.index', 'stock-outs.index', 'pendings.index', 'new-products.index',
]);

it('muestra secciones, obligatorios y el pie estándar en el formulario de producto', function (): void {
    Livewire::actingAs(actingAsAdmin())
        ->test('shopping::pages.product-index')
        ->assertSeeHtml('form-section-title')
        ->assertSee('Información farmacéutica')
        ->assertSeeHtml('class="form-required"')
        ->assertSeeHtml('wire:target="save"');
});

it('renderiza el formulario de ítems del pendiente', function (): void {
    $admin = actingAsAdmin();
    $pending = Pending::factory()->create(['destination_id' => $admin->service_point_id, 'user_id' => $admin->id]);

    $this->actingAs($admin)
        ->get(route('pendings.show', $pending))
        ->assertOk()
        ->assertSee('Cantidad');
});

it('renderiza el perfil con sus secciones', function (): void {
    $this->actingAs(actingAsAdmin())
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Datos de la cuenta')
        ->assertSee('Preferencias de farmacia');
});
