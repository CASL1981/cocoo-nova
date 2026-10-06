<?php

namespace Modules\Shopping\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Livewire\Livewire;

class ShoppingServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Shopping';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'shopping';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    // protected array $commands = [];

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    /**
     * Define module schedules.
     *
     * @param $schedule
     */
    // protected function configureSchedules(Schedule $schedule): void
    // {
    //     $schedule->command('inspire')->hourly();
    // }


    public function register(): void
    {
         parent::register();
    }

    public function boot(): void
    {
        parent::boot();

        // El componente de CLASE (ProductFormModal) sí se resuelve por el
        // registro dinámico de addNamespace(), que funcionó correctamente
        // en Livewire::test() — este no es el que está fallando.
        Livewire::addNamespace(
            namespace: 'shopping',
            classNamespace: 'Modules\\Shopping\\App\\Livewire',
            classPath: module_path('Shopping', 'app/Livewire'),
            classViewPath: module_path('Shopping', 'resources/views/livewire'),
        );
    }
}
