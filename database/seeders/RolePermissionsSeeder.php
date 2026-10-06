<?php

namespace Database\Seeders;

use App\Actions\Permissions\SeedModulePermissions;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        (new SeedModulePermissions())->execute('roles');
    }
}
