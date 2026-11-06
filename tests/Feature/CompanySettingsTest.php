<?php

use App\Settings\CompanySettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('niega el acceso sin permiso', function (): void {
    $this->actingAs(userWith())
        ->get(route('company-settings.index'))
        ->assertForbidden();
});

it('muestra los datos iniciales de la empresa con permiso de lectura', function (): void {
    $this->actingAs(userWith('company-settings-read'))
        ->get(route('company-settings.index'))
        ->assertOk()
        ->assertSee('COODESCOR')
        ->assertSee('812001561');
});

it('no permite editar con solo permiso de lectura', function (): void {
    Livewire::actingAs(userWith('company-settings-read'))
        ->test('pages::company-settings')
        ->call('openEdit')
        ->assertForbidden();
});

it('actualiza los datos y el logo de la empresa', function (): void {
    Storage::fake('public');

    Livewire::actingAs(userWith(['company-settings-read', 'company-settings-update']))
        ->test('pages::company-settings')
        ->call('openEdit')
        ->assertSet('form.legal_name', 'COODESCOR')
        ->set('form.legal_name', 'Cooperativa de Prueba')
        ->set('form.nit', '812001561-1')
        ->set('form.city', 'Montería')
        ->set('form.email', 'contacto@example.com')
        ->set('logo', UploadedFile::fake()->image('logo.png'))
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('show', false)
        ->assertSee('Cooperativa de Prueba');

    $settings = app(CompanySettings::class)->refresh();

    expect($settings->legal_name)->toBe('Cooperativa de Prueba')
        ->and($settings->nit)->toBe('812001561-1')
        ->and($settings->city)->toBe('Montería')
        ->and($settings->logo_path)->not->toBeNull();

    Storage::disk('public')->assertExists($settings->logo_path);
});

it('quita el logo y borra el archivo anterior', function (): void {
    Storage::fake('public');
    $path = UploadedFile::fake()->image('logo.png')->store('company', 'public');
    $settings = app(CompanySettings::class);
    $settings->logo_path = $path;
    $settings->save();

    Livewire::actingAs(userWith(['company-settings-read', 'company-settings-update']))
        ->test('pages::company-settings')
        ->call('openEdit')
        ->set('removeLogo', true)
        ->call('save')
        ->assertHasNoErrors();

    expect(app(CompanySettings::class)->refresh()->logo_path)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

it('valida el NIT, el correo y el tipo de logo', function (): void {
    Storage::fake('public');

    Livewire::actingAs(userWith(['company-settings-read', 'company-settings-update']))
        ->test('pages::company-settings')
        ->call('openEdit')
        ->set('form.nit', '81200A')
        ->set('form.email', 'no-es-correo')
        ->call('save')
        ->assertHasErrors(['form.nit', 'form.email']);

    Livewire::actingAs(userWith(['company-settings-read', 'company-settings-update']))
        ->test('pages::company-settings')
        ->call('openEdit')
        ->set('logo', UploadedFile::fake()->create('logo.pdf', 10, 'application/pdf'))
        ->call('save')
        ->assertHasErrors(['logo']);
});
