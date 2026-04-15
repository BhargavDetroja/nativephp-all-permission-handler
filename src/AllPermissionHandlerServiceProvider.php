<?php

namespace Nativephp\AllPermissionHandler;

use Illuminate\Support\ServiceProvider;
use Nativephp\AllPermissionHandler\Commands\CopyAssetsCommand;

class AllPermissionHandlerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/all-permission-handler.php', 'all-permission-handler');

        $this->app->singleton(AllPermissionHandler::class, function () {
            return new AllPermissionHandler;
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/all-permission-handler.php' => config_path('all-permission-handler.php'),
            ], 'all-permission-handler-config');

            $this->commands([
                CopyAssetsCommand::class,
            ]);
        }
    }
}
