<?php

use App\Actions\Permissions\SeedModulePermissions;
use App\Models\Role;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\AllModulePermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
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
        $this->seed([AllModulePermissionsSeeder::class]);
    })
    ->in('Feature', 'Browser');

// Capturas de la guía: el primer arranque del navegador puede tardar más que los 5 s por defecto.
pest()->browser()->timeout(15000);

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
        (new SeedModulePermissions)->execute($module);
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

/**
 * Captura para la guía de usuario (tests/Browser/Guide): marca los elementos indicados con un
 * recuadro numerado y guarda la imagen en resources/guide/images/{tema}/{archivo}.png.
 *
 * Si un selector ya no existe la prueba falla: así se detecta que una pantalla cambió y la guía
 * necesita revisión. Se ejecuta con `composer guide:screenshots`.
 *
 * @param  string  $image  Ruta relativa sin extensión, ej. 'providers-basic/01-add-button'.
 * @param  array<int, array{0: string, 1?: string}>  $marks  Pares [selector CSS, etiqueta del recuadro].
 * @param  string|null  $element  Selector a recortar (ej. '.modal-content'); null captura la ventana visible.
 */
function guideShot(object $page, string $image, array $marks = [], ?string $element = null): void
{
    $script = <<<'JS'
        (() => {
            document.querySelectorAll('.guide-shot-mark').forEach((mark) => mark.remove());
            document.querySelectorAll('[data-bs-target="#live-customizer"]').forEach((el) => el.style.display = 'none');
            __MARKS__.forEach(([selector, label]) => {
                const target = document.querySelector(selector);
                if (! target) throw new Error('Guía: no existe el elemento ' + selector);
                const rect = target.getBoundingClientRect();
                const box = document.createElement('div');
                box.className = 'guide-shot-mark';
                Object.assign(box.style, {
                    position: 'absolute', zIndex: 2147483647, pointerEvents: 'none',
                    left: (rect.left + scrollX - 4) + 'px', top: (rect.top + scrollY - 4) + 'px',
                    width: (rect.width + 8) + 'px', height: (rect.height + 8) + 'px',
                    border: '3px solid #e8590c', borderRadius: '8px', boxShadow: '0 0 0 4px rgba(232, 89, 12, .25)',
                });
                if (label) {
                    const badge = document.createElement('span');
                    badge.textContent = label;
                    Object.assign(badge.style, {
                        position: 'absolute', top: '-13px', left: '-13px', minWidth: '24px', height: '24px', padding: '0 6px',
                        borderRadius: '12px', background: '#e8590c', color: '#fff', font: '600 13px/24px sans-serif', textAlign: 'center',
                    });
                    box.appendChild(badge);
                }
                document.body.appendChild(box);
            });
        })()
        JS;

    $page->script(str_replace('__MARKS__', (string) json_encode(array_values($marks)), $script));

    $name = 'guide--'.str_replace('/', '--', $image);
    $element === null ? $page->screenshot(fullPage: false, filename: $name) : $page->screenshotElement($element, $name);

    $target = resource_path("guide/images/{$image}.png");
    File::ensureDirectoryExists(dirname($target));
    File::move(base_path("tests/Browser/Screenshots/{$name}.png"), $target);
}

/**
 * Crea un usuario con los permisos indicados (atajo usado en todos los CRUD).
 */
function userWith(string|array $permissions = []): User
{
    return User::factory()->create()->givePermissionTo($permissions);
}
