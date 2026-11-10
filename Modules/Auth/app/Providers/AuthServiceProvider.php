<?php

namespace Modules\Auth\Providers;

use Illuminate\Routing\Router;
use Modules\Auth\Http\Middleware\EnsureUserIsAdmin;
use Nwidart\Modules\Support\ModuleServiceProvider;

class AuthServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Auth';

    protected string $nameLower = 'auth';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        $this->app->make(Router::class)->aliasMiddleware('admin', EnsureUserIsAdmin::class);
    }
}
