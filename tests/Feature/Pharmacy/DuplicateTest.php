<?php

use App\Enums\Status;
use App\Models\ServicePoint;
use App\Models\User;
use Livewire\Livewire;
use Modules\Pharmacy\App\Enums\PendingStatus;
use Modules\Pharmacy\App\Models\Pending;
use Modules\Pharmacy\App\Models\PendingItem;
use Modules\Pharmacy\App\Models\StockOut;

it('duplica un agotado como inactivo', function (): void {
    $source = StockOut::factory()->create(['status' => Status::Active]);

    Livewire::actingAs(userWith(['stock-outs-read', 'stock-outs-create']))
        ->test('pharmacy::pages.stock-out-index')
        ->set('selected', [(string) $source->id])
        ->call('duplicateSelected')
        ->assertDispatched('toast');

    $copy = StockOut::query()->whereKeyNot($source->id)->sole();
    expect($copy->generic_code)->toBe($source->generic_code)
        ->and($copy->circular_number)->toBe($source->circular_number)
        ->and($copy->status)->toBe(Status::Inactive);
});

it('duplica un pendiente terminado como activo, sin ítems y con quien duplica como solicitante', function (): void {
    $user = userWith(['pendings-read', 'pendings-create']);
    $source = Pending::factory()->create([
        'destination_id' => $user->service_point_id,
        'status' => PendingStatus::Finished,
        'user_id' => User::factory()->create()->id,
    ]);
    PendingItem::factory()->create(['pending_id' => $source->id]);

    Livewire::actingAs($user)
        ->test('pharmacy::pages.pending-index')
        ->set('selected', [(string) $source->id])
        ->call('duplicateSelected');

    $copy = Pending::query()->whereKeyNot($source->id)->sole();
    expect($copy->status)->toBe(PendingStatus::Active)
        ->and($copy->user_id)->toBe($user->id)
        ->and($copy->category)->toBe($source->category)
        ->and($copy->destination_id)->toBe($source->destination_id)
        ->and($copy->items()->count())->toBe(0);
});

it('una farmacia no duplica pendientes de otra sede; Oficina Principal sí', function (): void {
    $source = Pending::factory()->create(['destination_id' => ServicePoint::factory()->create()->id]);

    Livewire::actingAs(userWith(['pendings-read', 'pendings-create']))
        ->test('pharmacy::pages.pending-index')
        ->set('selected', [(string) $source->id])
        ->call('duplicateSelected')
        ->assertForbidden();

    $headquarters = ServicePoint::factory()->create(['code' => ServicePoint::HEADQUARTERS_CODE]);
    $hqUser = userWith(['pendings-read', 'pendings-create']);
    $hqUser->update(['service_point_id' => $headquarters->id]);

    Livewire::actingAs($hqUser)
        ->test('pharmacy::pages.pending-index')
        ->set('selected', [(string) $source->id])
        ->call('duplicateSelected');

    expect(Pending::query()->count())->toBe(2);
});
