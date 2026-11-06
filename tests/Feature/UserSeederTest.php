<?php

use App\Models\Role;
use App\Models\ServicePoint;
use App\Models\User;
use Database\Seeders\ProcessSeeder;
use Database\Seeders\ServicePointSeeder;
use Database\Seeders\UserSeeder;

beforeEach(function (): void {
    foreach (['Administrador', 'Contabilidad', 'Operaciones'] as $name) {
        Role::factory()->create(['name' => $name]);
    }

    $this->seed([ServicePointSeeder::class, ProcessSeeder::class]);
});

it('siembra los usuarios del sistema anterior con su proceso, sede y rol', function (): void {
    $this->seed(UserSeeder::class);

    expect(User::count())->toBe(count(UserSeeder::USERS));

    $pharmacy = User::with(['process', 'servicePoint'])->where('email', 'farmacia17valencia@coodescor.org.co')->first();
    expect($pharmacy->process->name)->toBe('Logística y Operaciones')
        ->and($pharmacy->servicePoint->code)->toBe('1100')
        ->and($pharmacy->hasRole('Operaciones'))->toBeTrue()
        ->and($pharmacy->can_request_supplies)->toBeTrue()
        ->and($pharmacy->must_change_password)->toBeTrue();

    $admin = User::with('servicePoint')->where('email', 'sistemas@coodescor.org.co')->first();
    expect($admin->hasRole('Administrador'))->toBeTrue()
        ->and($admin->servicePoint->code)->toBe(ServicePoint::HEADQUARTERS_CODE);
});

it('es idempotente: actualiza los datos por correo sin duplicar ni cambiar la clave', function (): void {
    $this->seed(UserSeeder::class);
    User::where('email', 'sistemas@coodescor.org.co')->update(['name' => 'ROSANA', 'identification' => '1']);
    $password = User::where('email', 'sistemas@coodescor.org.co')->value('password');

    $this->seed(UserSeeder::class);

    $user = User::where('email', 'sistemas@coodescor.org.co')->first();
    expect(User::count())->toBe(count(UserSeeder::USERS))
        ->and($user->fullName())->toBe('CARLOS CARVAJAL')
        ->and($user->identification)->toBe('78032495')
        ->and($user->password)->toBe($password);
});
