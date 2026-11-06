<?php

namespace App\Providers;

use App\Models\User;
use App\Observers\UserObserver;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        User::observe(UserObserver::class);

        $this->configureDefaults();

        Schema::defaultStringLength(191);

        Paginator::useBootstrapFive();

        Model::preventLazyLoading(! app()->isProduction());

        /**
         * Columnas estándar de auditoría (timestamps, eliminación lógica y userstamps)
         * con comentario en cada columna, como exige la convención del proyecto.
         */
        Blueprint::macro('auditColumns', function (): void {
            /** @var Blueprint $this */
            $this->timestamp('created_at')->nullable()->comment('Fecha de creación del registro.');
            $this->timestamp('updated_at')->nullable()->comment('Fecha de la última modificación.');
            $this->softDeletes()->comment('Fecha de eliminación lógica.');
            $this->unsignedBigInteger('created_by')->nullable()->index()->comment('Usuario que creó el registro.');
            $this->unsignedBigInteger('updated_by')->nullable()->index()->comment('Usuario que modificó el registro por última vez.');
            $this->unsignedBigInteger('deleted_by')->nullable()->index()->comment('Usuario que eliminó el registro.');
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
