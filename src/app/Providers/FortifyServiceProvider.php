<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;
use App\Actions\Fortify\CreateNewUser;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use App\Actions\Fortify\LoginResponse;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use App\Actions\Fortify\RegisterResponse;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use App\Actions\Fortify\LogoutResponse;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\RegisteredUserController;
use Laravel\Fortify\Actions\AttemptToAuthenticate;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(CreatesNewUsers::class, CreateNewUser::class);
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
        $this->app->singleton(RegisterResponseContract::class, RegisterResponse::class);
        $this->app->singleton(LogoutResponseContract::class, LogoutResponse::class);

        // FortifyのコントローラにStatefulGuard(web)を注入
        $this->app->when([
            AuthenticatedSessionController::class,
            RegisteredUserController::class,
            AttemptToAuthenticate::class,
        ])->needs(StatefulGuard::class)->give(function () {
            return Auth::guard(config('fortify.guard', 'web'));
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Fortify::loginView(function () {
            return view('auth.login');
        });

        Fortify::registerView(function () {
            return view('auth.register');
        });
    }
}
