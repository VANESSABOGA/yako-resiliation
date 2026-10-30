<?php

namespace Modules\Resiliation\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class ResiliationServiceProvider extends ServiceProvider
{
    /**
     * Enregistrement des services métier du module.
     */
    public function register(): void
    {
        // Services (singletons) - on les activera au fur et à mesure des sprints
        // $this->app->singleton(\Modules\Resiliation\Services\SmsService::class);
        // $this->app->singleton(\Modules\Resiliation\Services\PaymentLinkService::class);
        // $this->app->singleton(\Modules\Resiliation\Services\KpiService::class);
    }

    /**
     * Boot du module : migrations, vues, routes, commandes.
     */
    public function boot(): void
    {
        // Migrations du module
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        // Vues du module (accessibles via view('resiliation::nom.vue'))
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'resiliation');

        // Routes Web (agent + manager)
        $this->registerWebRoutes();

        // Routes API (webhooks SMS + JEKO)
        $this->registerApiRoutes();

        // Commandes Artisan
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Modules\Resiliation\Console\Commands\InitCampagneCommand::class,
            ]);
        }
    }

    protected function registerWebRoutes(): void
    {
        Route::middleware(['web'])
            ->prefix('resiliation')
            ->name('resiliation.')
            ->group(__DIR__ . '/../routes/web.php');
    }

    protected function registerApiRoutes(): void
    {
        Route::middleware(['api'])
            ->prefix('api/resiliation')
            ->name('api.resiliation.')
            ->group(__DIR__ . '/../routes/api.php');
    }
}
