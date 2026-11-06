<?php

/*
 * Capturas de la guía de usuario: sección Talento Humano (resources/guide/hr/*.md).
 * Usa los cargos reales del organigrama (no son datos personales) y una lista ficticia.
 */

use Modules\HumanResources\App\Models\HrCharacteristic;
use Modules\HumanResources\App\Models\HrCharacteristicOption;
use Modules\HumanResources\Database\Seeders\PositionSeeder;

/** Hace clic con JavaScript en el primer elemento del selector cuyo texto contiene el indicado. */
function hrGuideClickText(object $page, string $selector, string $text, float $seconds = 1): void
{
    $page->script('[...document.querySelectorAll('.json_encode($selector).')].find((el) => el.textContent.includes('.json_encode($text).')).click()');
    $page->wait($seconds);
}

/** Lanza un evento de Livewire (abre los modales igual que sus botones) y espera la respuesta. */
function hrGuideDispatch(object $page, string $event, array $params = [], float $seconds = 1): void
{
    $page->script('Livewire.dispatch('.json_encode($event).', '.json_encode((object) $params).')');
    $page->wait($seconds);
}

it('genera las capturas de características y cargos', function (): void {
    $admin = actingAsAdmin();
    $admin->update(['name' => 'Laura', 'lastname' => 'Méndez Ríos', 'email' => 'laura.mendez@empresa.test']);
    $this->actingAs($admin);
    $this->seed(PositionSeeder::class);

    $contracts = HrCharacteristic::factory()->create(['name' => 'Tipos de contrato', 'code' => 'contract_type']);
    foreach (['Término fijo', 'Término indefinido', 'Obra o labor', 'Aprendizaje'] as $name) {
        HrCharacteristicOption::factory()->create(['hr_characteristic_id' => $contracts->id, 'name' => $name]);
    }

    $page = visit('/human-resources/characteristics')->resize(1920, 1080);
    $page->wait(2);
    hrGuideClickText($page, '#page_layout tbody tr', 'Cargos', 2);

    guideShot($page, 'hr-characteristics/01-overview', [
        ['#page_layout tr.table-active', '1'],
        ['#page_layout .col-xxl-7 thead tr:nth-child(2) th:nth-child(3)', '2'],
        ['#page_layout .col-xxl-7 thead tr:first-child th:nth-child(4)', '3'],
        ['#page_layout .col-xxl-7 [aria-label="Agregar"]', '4'],
    ], '#page_layout');

    hrGuideDispatch($page, 'open-hr-characteristic-option-edit', [
        'optionId' => HrCharacteristicOption::where('name', 'Coordinador de Compras')->value('id'),
    ]);

    guideShot($page, 'hr-characteristics/02-position-form', [
        ['.app-modal.show #hr-option-name', '1'], ['.app-modal.show #hr-option-process', '2'], ['.app-modal.show #hr-option-parent', '3'],
    ], '.app-modal.show .modal-content');

    $page = visit('/human-resources/characteristics')->resize(1920, 1080);
    hrGuideDispatch($page, 'open-hr-characteristic-edit', ['characteristicId' => HrCharacteristic::where('code', 'positions')->value('id')]);

    guideShot($page, 'hr-characteristics/03-characteristic-form', [
        ['.app-modal.show #hr-characteristic-code', '1'],
    ], '.app-modal.show .modal-content');
});
