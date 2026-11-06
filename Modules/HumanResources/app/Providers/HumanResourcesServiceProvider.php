<?php

namespace Modules\HumanResources\Providers;

use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Modules\HumanResources\App\Models\HrCharacteristic;
use Modules\HumanResources\App\Models\HrCharacteristicOption;
use Modules\HumanResources\App\Policies\HrCharacteristicOptionPolicy;
use Modules\HumanResources\App\Policies\HrCharacteristicPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

/**
 * Módulo de Talento Humano: características propias del área (cargos, tipos de contrato…),
 * separadas de las características generales de la aplicación, y más adelante empleados.
 */
class HumanResourcesServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'HumanResources';

    protected string $nameLower = 'humanresources';

    /** @var string[] */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        // Páginas (pages.*) y componentes de clase del módulo.
        Livewire::addNamespace(
            namespace: 'humanresources',
            classNamespace: 'Modules\\HumanResources\\App\\Livewire',
            classPath: module_path('HumanResources', 'app/Livewire'),
            classViewPath: module_path('HumanResources', 'resources/views/livewire'),
        );

        // Registro explícito: el descubrimiento automático de Policies no cubre el namespace del módulo.
        Gate::policy(HrCharacteristic::class, HrCharacteristicPolicy::class);
        Gate::policy(HrCharacteristicOption::class, HrCharacteristicOptionPolicy::class);
    }
}
