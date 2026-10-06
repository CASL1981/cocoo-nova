<?php

use App\Livewire\Users\UserFormModal;
use App\Models\Role;
use App\Models\User;
use App\Notifications\Users\TemporaryPasswordAssigned;
use App\Notifications\Users\UserCreatedConfirmation;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

it('asigna clave temporal y notifica al crear un usuario', function () {
    Notification::fake();

    $admin = actingAsAdmin();
    $role = Role::factory()->create();

    Livewire::actingAs($admin)
        ->test(UserFormModal::class)
        ->call('openCreate')
        ->set('form.identification', '1020304050')
        ->set('form.name', 'Ana')
        ->set('form.lastname', 'Ruiz')
        ->set('form.email', 'ana@example.com')
        ->set('form.role_id', (string) $role->id)
        ->call('save')
        ->assertHasNoErrors();

    $newUser = User::where('email', 'ana@example.com')->first();

    expect($newUser->must_change_password)->toBeTrue();

    Notification::assertSentTo($newUser, TemporaryPasswordAssigned::class);
    Notification::assertSentTo($admin, UserCreatedConfirmation::class);
});

it('obliga a cambiar la clave temporal antes de acceder a otras pantallas', function () {
    $role = Role::factory()->create();
    $role->syncPermissions(['users-read']);

    $user = User::factory()->create(['role_id' => $role->id, 'must_change_password' => true]);
    $user->assignRole($role);

    $this->actingAs($user)->get(route('users.index'))
        ->assertRedirect(route('password.force-change'));
});

it('permite cambiar la contraseña y libera el acceso', function () {
    $user = User::factory()->create(['must_change_password' => true]);

    Livewire::actingAs($user)
        ->test('pages::force-password-change')
        ->set('password', 'Str0ng-Passw0rd!')
        ->set('password_confirmation', 'Str0ng-Passw0rd!')
        ->call('save');

    expect($user->fresh()->must_change_password)->toBeFalse();
});
