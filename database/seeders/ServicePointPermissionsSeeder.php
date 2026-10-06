<?php

namespace Database\Seeders;

use App\Actions\Permissions\SeedModulePermissions;
use Illuminate\Database\Seeder;

class ServicePointPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        (new SeedModulePermissions())->execute('service-points');
    }
}
