<?php

namespace Database\Seeders;

use App\Actions\Permissions\SeedModulePermissions;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        (new SeedModulePermissions())->execute('users');
    }
}
