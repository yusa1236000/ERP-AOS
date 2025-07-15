<?php
// app/Providers/BladeServiceProvider.php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use App\Services\AuthService;

class BladeServiceProvider extends ServiceProvider
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
        $this->registerRoleDirectives();
        $this->registerPermissionDirectives();
        $this->registerModuleDirectives();
    }

    /**
     * Register role-based Blade directives
     */
    private function registerRoleDirectives(): void
    {
        // @hasrole('admin')
        Blade::if('hasrole', function ($role) {
            $authService = app(AuthService::class);
            return $authService->hasRole($role);
        });

        // @hasanyrole(['admin', 'manager'])
        Blade::if('hasanyrole', function ($roles) {
            $authService = app(AuthService::class);
            return $authService->hasRole($roles);
        });

        // @hasallroles(['admin', 'manager'])
        Blade::if('hasallroles', function ($roles) {
            $authService = app(AuthService::class);
            if (!is_array($roles)) {
                $roles = [$roles];
            }
            return $authService->user()?->hasAllRoles($roles) ?? false;
        });

        // @role('admin') ... @endrole
        Blade::directive('role', function ($role) {
            return "<?php if(app(\App\Services\AuthService::class)->hasRole({$role})): ?>";
        });
        Blade::directive('endrole', function () {
            return "<?php endif; ?>";
        });

        // @unlessrole('admin') ... @endunlessrole
        Blade::directive('unlessrole', function ($role) {
            return "<?php if(!app(\App\Services\AuthService::class)->hasRole({$role})): ?>";
        });
        Blade::directive('endunlessrole', function () {
            return "<?php endif; ?>";
        });
    }

    /**
     * Register permission-based Blade directives
     */
    private function registerPermissionDirectives(): void
    {
        // @haspermission('inventory.create')
        Blade::if('haspermission', function ($permission) {
            $authService = app(AuthService::class);
            return $authService->hasPermission($permission);
        });

        // @hasanypermission(['inventory.create', 'inventory.update'])
        Blade::if('hasanypermission', function ($permissions) {
            $authService = app(AuthService::class);
            return $authService->hasAnyPermission($permissions);
        });

        // @hasallpermissions(['inventory.create', 'inventory.update'])
        Blade::if('hasallpermissions', function ($permissions) {
            $authService = app(AuthService::class);
            return $authService->hasAllPermissions($permissions);
        });

        // @permission('inventory.create') ... @endpermission
        Blade::directive('permission', function ($permission) {
            return "<?php if(app(\App\Services\AuthService::class)->hasPermission({$permission})): ?>";
        });
        Blade::directive('endpermission', function () {
            return "<?php endif; ?>";
        });

        // @unlesspermission('inventory.create') ... @endunlesspermission
        Blade::directive('unlesspermission', function ($permission) {
            return "<?php if(!app(\App\Services\AuthService::class)->hasPermission({$permission})): ?>";
        });
        Blade::directive('endunlesspermission', function () {
            return "<?php endif; ?>";
        });
    }

    /**
     * Register module access directives
     */
    private function registerModuleDirectives(): void
    {
        // @canaccess('inventory', 'create')
        Blade::if('canaccess', function ($module, $action = 'read') {
            $authService = app(AuthService::class);
            return $authService->canAccess($module, $action);
        });

        // @canread('inventory')
        Blade::if('canread', function ($module) {
            $authService = app(AuthService::class);
            return $authService->canAccess($module, 'read');
        });

        // @cancreate('inventory')
        Blade::if('cancreate', function ($module) {
            $authService = app(AuthService::class);
            return $authService->canAccess($module, 'create');
        });

        // @canupdate('inventory')
        Blade::if('canupdate', function ($module) {
            $authService = app(AuthService::class);
            return $authService->canAccess($module, 'update');
        });

        // @candelete('inventory')
        Blade::if('candelete', function ($module) {
            $authService = app(AuthService::class);
            return $authService->canAccess($module, 'delete');
        });

        // @canapprove('inventory')
        Blade::if('canapprove', function ($module) {
            $authService = app(AuthService::class);
            return $authService->canAccess($module, 'approve');
        });

        // @issuperadmin
        Blade::if('issuperadmin', function () {
            $authService = app(AuthService::class);
            return $authService->isSuperAdmin();
        });

        // @isadmin
        Blade::if('isadmin', function () {
            $authService = app(AuthService::class);
            return $authService->isAdmin();
        });

        // @module('inventory') ... @endmodule
        Blade::directive('module', function ($module) {
            return "<?php if(app(\App\Services\AuthService::class)->canAccess({$module})): ?>";
        });
        Blade::directive('endmodule', function () {
            return "<?php endif; ?>";
        });
    }
}

// Don't forget to register this provider in config/app.php:
/*
'providers' => [
// ... other providers
App\Providers\BladeServiceProvider::class,
],
*/