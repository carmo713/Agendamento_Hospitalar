<?php
// app/Providers/AuthServiceProvider.php
namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->registerPolicies();

        // Definindo Gates para cada papel
        Gate::define('is-admin', fn($user) => $user->isAdmin());
        Gate::define('is-doctor', fn($user) => $user->isDoctor());
        Gate::define('is-patient', fn($user) => $user->isPatient());
    }
}