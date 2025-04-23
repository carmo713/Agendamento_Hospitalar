<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->routes(function () {
            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            Route::prefix('api')
                ->middleware('api')
                ->group(base_path('routes/api.php'));
        });
    }
    public function redirectTo()
{
    if (auth()->user()->hasRole('admin')) {
        return '/admin/dashboard';
    } elseif (auth()->user()->hasRole('doctor')) {
        return '/doctor/dashboard';
    } elseif (auth()->user()->hasRole('patient')) {
        return '/patient/dashboard';
    }
    
    return '/home';
}
}