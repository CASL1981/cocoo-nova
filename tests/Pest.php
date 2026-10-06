<?php

use App\Actions\Permissions\SeedModulePermissions;
use App\Models\Role;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\RolePermissionsSeeder;
use Database\Seeders\ServicePointPermissionsSeeder;
use Database\Seeders\UserPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Shopping\Database\Seeders\ProductPermissionsSeeder;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        $this->seed([
            RolePermissionsSeeder::class,
            UserPermissionsSeeder::class,
            ServicePointPermissionsSeeder::class,
            ProductPermissionsSeeder::class
            ]);
    })
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}


function actingAsAdmin(): User
{
    foreach (array_keys(PermissionCatalog::MODULES) as $module) {
        (new SeedModulePermissions())->execute($module);
    }

    $role = Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
    $role->syncPermissions(
        collect(PermissionCatalog::MODULES)->keys()
            ->flatMap(fn ($module) => PermissionCatalog::allForModule($module))
            ->all()
    );

    $user = User::factory()->create(['role_id' => $role->id]);
    $user->assignRole($role);

    return $user;
}
