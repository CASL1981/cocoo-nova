<?php

namespace Modules\Administrative\Providers;

use Livewire\Livewire;
use Nwidart\Modules\Support\ModuleServiceProvider;

/**
 * Módulo Administrativo: proveedores administrativos y, más adelante, requisiciones de
 * aseo, papelería y cafetería, kardex y órdenes de compra (doc/especificacion-modulo-administrativo.md).
 */
class AdministrativeServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Administrative';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'administrative';

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        // Páginas (pages.*) y componentes de clase del módulo.
        Livewire::addNamespace(
            namespace: 'administrative',
            classNamespace: 'Modules\\Administrative\\App\\Livewire',
            classPath: module_path('Administrative', 'app/Livewire'),
            classViewPath: module_path('Administrative', 'resources/views/livewire'),
        );
    }
}
