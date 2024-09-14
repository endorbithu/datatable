<?php

namespace Endorbit\Datatable\Providers;

use Endorbit\Datatable\Contracts\DatatableServiceInterface;
use Endorbit\Datatable\Contracts\OperationServiceInterface;
use Endorbit\Datatable\Services\DatatableService;
use Endorbit\Datatable\Services\DatatableServiceSelector;
use Endorbit\Datatable\Services\OperationService;

class DatatableServiceProvider extends \Illuminate\Support\ServiceProvider
{

    public function boot()
    {

        include __DIR__ . '/../../routes/web.php';


        $this->publishes([
            __DIR__ . '/../../config/datatable.php' => config_path('datatable.php'),
        ]);

        $this->publishes([
            __DIR__ . '/../../public' => public_path('vendor/datatable'),
        ]);

        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        $this->publishes([
            __DIR__ . '/../../database/seeders/DatatableSeeder.php' => database_path('seeders/DatatableSeeder.php'),
        ]);


    }

    public function register()
    {
        $this->app->bind(DatatableServiceInterface::class, function () {
            return (new DatatableService());
        });


        $this->app->bind('datatable', function () {
            return new DatatableServiceSelector();
        });

        $loader = \Illuminate\Foundation\AliasLoader::getInstance();
        $loader->alias('Datatable', \Endorbit\Datatable\Services\Datatable::class);

        $this->loadViewsFrom(__DIR__ . '/../../views', 'datatable');

        parent::register();
    }
}

