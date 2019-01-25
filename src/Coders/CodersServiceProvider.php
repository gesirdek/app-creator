<?php

namespace Gesirdek\Coders;

use Gesirdek\Providers\RouteServiceProvider;
use Gesirdek\Support\Classify;
use Gesirdek\Coders\Model\Config;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Gesirdek\Coders\Console\CodeModelsCommand;
use Gesirdek\Coders\Model\Factory as ModelFactory;
use Illuminate\Support\Facades\Artisan;

class CodersServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/../Http/routes.php');
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../publish/config/models.php' => config_path('models.php'),
                __DIR__ . '/../../publish/js/' => resource_path('assets/js'),
                __DIR__ . '/../../publish/base/.babelrc' => base_path('.babelrc'),
                __DIR__ . '/../../publish/base/webpack.mix.js' => base_path('webpack.mix.js'),
                __DIR__ . '/../../publish/views/admin.blade.php' => resource_path('views/admin.blade.php'),
                __DIR__ . '/../../publish/lang/en/Component-form.php' => resource_path('lang/en/Component-form.php'),
                __DIR__ . '/../../publish/lang/tr/Component-form.php' => resource_path('lang/tr/Component-form.php'),
                __DIR__ . '/../../publish/app/Helper' => app_path('Helper.php'),
                __DIR__ . '/../../publish/app/Http/Kernel' => app_path('Http/Kernel.php'),
                __DIR__ . '/../../publish/app/Http/Middleware/CheckForMaintenanceMode' => app_path('Http/Middleware/CheckForMaintenanceMode.php'),
                __DIR__ . '/../../publish/app/Http/Middleware/CheckIpChangeMiddleware' => app_path('Http/Middleware/CheckIpChangeMiddleware.php'),
                __DIR__ . '/../../publish/app/Http/Middleware/EncryptCookies' => app_path('Http/Middleware/EncryptCookies.php'),
                __DIR__ . '/../../publish/app/Http/Middleware/LanguageMiddleware' => app_path('Http/Middleware/LanguageMiddleware.php'),
                __DIR__ . '/../../publish/app/Http/Middleware/RedirectIfAuthenticated' => app_path('Http/Middleware/RedirectIfAuthenticated.php'),
                __DIR__ . '/../../publish/app/Http/Middleware/RoleChecker' => app_path('Http/Middleware/RoleChecker.php'),
                __DIR__ . '/../../publish/app/Http/Middleware/TrimStrings' => app_path('Http/Middleware/TrimStrings.php'),
                __DIR__ . '/../../publish/app/Http/Middleware/TrustProxies' => app_path('Http/Middleware/TrustProxies.php'),
                __DIR__ . '/../../publish/app/Http/Middleware/VerifyCsrfToken' => app_path('Http/Middleware/VerifyCsrfToken.php'),
            ], 'gesirdek-models');
            //$this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

            $this->commands([
                CodeModelsCommand::class,
            ]);
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerModelFactory();
    }

    /**
     * Register Model Factory.
     *
     * @return void
     */
    protected function registerModelFactory()
    {
        $this->app->singleton(ModelFactory::class, function ($app) {
            return new ModelFactory(
                $app->make('db'),
                $app->make(Filesystem::class),
                new Classify(),
                new Config($app->make('config')->get('models'))
            );
        });
    }

    /**
     * @return array
     */
    public function provides()
    {
        return [ModelFactory::class];
    }
}
