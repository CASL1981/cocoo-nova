<?php

namespace Modules\Pharmacy\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Modules\Pharmacy\App\Models\InventoryBalance;
use Modules\Pharmacy\App\Models\NewProduct;
use Modules\Pharmacy\App\Models\Pending;
use Modules\Pharmacy\App\Models\PendingItem;
use Modules\Pharmacy\App\Policies\InventoryBalancePolicy;
use Modules\Pharmacy\App\Policies\NewProductPolicy;
use Modules\Pharmacy\App\Policies\PendingItemPolicy;
use Modules\Pharmacy\App\Policies\PendingPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;

class PharmacyServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Pharmacy';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'pharmacy';

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
     * @param  $schedule
     */
    // protected function configureSchedules(Schedule $schedule): void
    // {
    //     $schedule->command('inspire')->hourly();
    // }

    public function boot(): void
    {
        parent::boot();

        // Componentes de clase del módulo (modales reutilizables).
        Livewire::addNamespace(
            namespace: 'pharmacy',
            classNamespace: 'Modules\\Pharmacy\\App\\Livewire',
            classPath: module_path('Pharmacy', 'app/Livewire'),
            classViewPath: module_path('Pharmacy', 'resources/views/livewire'),
        );

        // Registro explícito: el descubrimiento automático de Policies no cubre el namespace del módulo.
        // Gate::policy(StockOut::class, StockOutPolicy::class);
        Gate::policy(Pending::class, PendingPolicy::class);
        Gate::policy(PendingItem::class, PendingItemPolicy::class);
        Gate::policy(InventoryBalance::class, InventoryBalancePolicy::class);
        Gate::policy(NewProduct::class, NewProductPolicy::class);
    }
}
