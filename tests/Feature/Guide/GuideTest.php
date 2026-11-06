<?php

use App\Support\GuideCatalog;
use App\Support\PermissionCatalog;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

it('muestra a todo usuario los temas generales y solo los temas de sus permisos', function (): void {
    $sections = Livewire::actingAs(userWith())->test('pages::guide')->get('sections');

    expect(array_keys($sections))->toBe(['start']);

    $sections = Livewire::actingAs(userWith('administrative-providers-read'))->test('pages::guide')->get('sections');

    expect(array_keys($sections))->toBe(['start', 'providers'])
        ->and($sections['providers'])->toHaveKey('providers-basic');
});

it('no abre un tema sin permiso aunque se pida por la URL', function (): void {
    Livewire::withQueryParams(['topic' => 'admin-users'])
        ->actingAs(userWith())
        ->test('pages::guide')
        ->assertSet('topic', 'getting-started')
        ->call('open', 'admin-users')
        ->assertSet('topic', 'getting-started')
        ->set('topic', 'admin-users')
        ->assertSee('no está disponible para tu usuario');
});

it('abre el tema de la pantalla desde el botón de ayuda', function (): void {
    Livewire::withQueryParams(['context' => 'administrative-providers.index'])
        ->actingAs(userWith('administrative-providers-read'))
        ->test('pages::guide')
        ->assertSet('topic', 'providers-overview');

    // Sin tema para la pantalla (o sin permiso) abre la introducción.
    Livewire::withQueryParams(['context' => 'users.index'])
        ->actingAs(userWith())
        ->test('pages::guide')
        ->assertSet('topic', 'getting-started');
});

it('busca en los temas visibles sin distinguir tildes ni mayúsculas', function (): void {
    $sections = Livewire::actingAs(userWith('company-settings-read'))
        ->test('pages::guide')
        ->set('search', 'OFICIAL cumplimiento')
        ->get('sections');

    expect($sections)->toHaveKey('admin')
        ->and(array_keys($sections['admin']))->toBe(['admin-company-settings']);
});

it('entrega las capturas solo a quien puede leer su tema', function (): void {
    $dir = GuideCatalog::imagesPath('admin-users');
    File::ensureDirectoryExists($dir);
    File::put($dir.'/zz-test.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='));

    try {
        $this->get(route('guide.image', ['path' => 'admin-users/zz-test.png']))->assertRedirect(route('login'));
        $this->actingAs(userWith())->get(route('guide.image', ['path' => 'admin-users/zz-test.png']))->assertNotFound();
        $this->actingAs(userWith('users-read'))->get(route('guide.image', ['path' => 'admin-users/zz-test.png']))->assertOk();
    } finally {
        File::delete($dir.'/zz-test.png');
    }
});

describe('integridad de la guía', function (): void {
    it('tiene secciones, permisos y pantallas que existen', function (string $slug): void {
        $topic = GuideCatalog::TOPICS[$slug];
        $permissions = collect(PermissionCatalog::MODULES)->keys()->flatMap(fn (string $module) => PermissionCatalog::allForModule($module));

        expect(GuideCatalog::SECTIONS)->toHaveKey($topic['section']);

        foreach ($topic['abilities'] as $ability) {
            expect($permissions)->toContain($ability);
        }

        foreach ($topic['routes'] as $route) {
            expect(Route::has($route))->toBeTrue("La ruta {$route} del tema {$slug} no existe");
        }
    })->with(array_keys(GuideCatalog::TOPICS));

    it('tiene su Markdown con capturas y enlaces válidos', function (string $slug): void {
        $markdown = GuideCatalog::markdown($slug);

        expect($markdown)->not->toBe('', "Falta resources/guide/.../{$slug}.md");

        preg_match_all('#\]\((images/[^)]+)\)#', $markdown, $images);
        foreach ($images[1] as $image) {
            expect($image)->toMatch('#^images/'.$slug.'/[a-z0-9-]+\.png$#')
                ->and(resource_path('guide/'.$image))->toBeFile();
        }

        preg_match_all('#\]\(guide:([^)]+)\)#', $markdown, $links);
        foreach ($links[1] as $link) {
            expect(GuideCatalog::TOPICS)->toHaveKey($link);
        }
    })->with(array_keys(GuideCatalog::TOPICS));
});
