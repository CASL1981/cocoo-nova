<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Shopping\Database\Seeders\ProductPermissionsSeeder;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        Role::factory()->createMany([
            ['name' => 'Administrador'],
            ['name' => 'Contabilidad'],
            ['name' => 'Operaciones'],
        ]);

        $this->call([
            UserPermissionsSeeder::class,
            RolePermissionsSeeder::class,
            ServicePointPermissionsSeeder::class,   // ← nuevo
            ServicePointSeeder::class,
            ProductPermissionsSeeder::class
        ]);

        User::factory(49)->create();

        $user = User::factory()->create([
            'identification' => '123456789',
            'name' => 'Carlos',
            'lastname' => 'Sibaja',
            'email' => 'contabilidad@coodescor.org.co',
            'role_id' => Role::where('name', 'Administrador')->first()->id,
        ]);

        $user->assignRole('Administrador');

        $role = Role::where('name', 'Administrador')->first();

        if ($role) {
            $role->syncPermissions(
                Permission::pluck('name')->toArray()
            );
        }
    }
}
