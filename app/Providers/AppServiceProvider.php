<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Directive to verify if a user has a specific permission on an entity
        Blade::directive('entityPermission', function ($expression) {
            list($permission, $entity) = explode(',', str_replace(['(', ')', ' '], '', $expression));
            return "<?php if(auth()->check() && auth()->user()->hasPermission({$permission}, {$entity})): ?>";
        });
        
        Blade::directive('endEntityPermission', function () {
            return "<?php endif; ?>";
        });
        
        // Directive to verify if a user has access to an entity
        Blade::directive('canAccess', function ($entity) {
            return "<?php if(auth()->check() && auth()->user()->canAccess({$entity})): ?>";
        });
        
        Blade::directive('endCanAccess', function () {
            return "<?php endif; ?>";
        });
    }
} 