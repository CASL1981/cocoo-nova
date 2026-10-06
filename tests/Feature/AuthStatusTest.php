<?php

use App\Enums\UserStatus;
use App\Models\User;

it('impide iniciar sesión a un usuario pendiente', function () {
    $user = User::factory()->create([
        'status' => UserStatus::Pending,
        'password' => bcrypt('password'),
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('impide iniciar sesión a un usuario inactivo', function () {
    $user = User::factory()->create([
        'status' => UserStatus::Inactive,
        'password' => bcrypt('password'),
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('permite iniciar sesión a un usuario activo', function () {
    $user = User::factory()->create([
        'status' => UserStatus::Active,
        'password' => bcrypt('password'),
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect();

    $this->assertAuthenticatedAs($user);
});

it('expulsa a un usuario cuya sesión sigue activa pero fue desactivado', function () {
    $role = \App\Models\Role::factory()->create();
    $role->syncPermissions(['users-read']);

    $user = User::factory()->create([
        'status' => UserStatus::Active,
        'role_id' => $role->id,
    ]);
    $user->assignRole($role);

    $this->actingAs($user)->get(route('users.index'))->assertOk();

    $user->update(['status' => UserStatus::Inactive]);

    $this->actingAs($user)->get(route('users.index'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});
