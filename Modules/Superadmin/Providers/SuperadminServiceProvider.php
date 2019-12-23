<?php

namespace Modules\Superadmin\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

use Modules\Superadmin\Entities\Subscription;
use Illuminate\Support\Facades\View;

use Illuminate\Console\Scheduling\Schedule;

use App\System;

class SuperadminServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerFactories();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        view::composer('superadmin::layouts.partials.active_subscription', function($view){
            $business_id = session()->get('user.business_id');
            $is_installed = \App\Utils\ModuleUtil::isInstalledSuperadmin();
            if($is_installed){
                $__subscription = Subscription::active_subscription($business_id);
            } else {
                $__subscription = null;
            }

            $view->with(compact('__subscription'));
        });

        //Set superadmin currency
        view::composer(['superadmin::layouts.partials.currency'], function($view){
            $__system_currency = System::getCurrency();
            $view->with(compact('__system_currency'));
        });

        $this->registerScheduleCommands();
    }

    public function registerScheduleCommands()
    {
        $env = config('app.env');
        //schedule command for sending subscription expiry alert
        if ($env === 'live') {
            $this->app->booted(function () {
                $schedule = $this->app->make(Schedule::class);
                $schedule->command('pos:sendSubscriptionExpiryAlert')->daily();
            });
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerCommands();
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('superadmin.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'superadmin'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/superadmin');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/superadmin';
        }, \Config::get('view.paths')), [$sourcePath]), 'superadmin');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/superadmin');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'superadmin');
        } else {
            $this->loadTranslationsFrom(__DIR__ .'/../Resources/lang', 'superadmin');
        }
    }

    /**
     * Register an additional directory of factories.
     * 
     * @return void
     */
    public function registerFactories()
    {
        if (! app()->environment('production')) {
            app(Factory::class)->load(__DIR__ . '/../Database/factories');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }

    /**
     * Register commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
        $this->commands([
            \Modules\Superadmin\Console\SubscriptionExpiryAlert::class
        ]);
    }
}
