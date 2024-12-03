<?php

namespace PermisologySystem\PermisologySystem\Services;

use PermisologySystem\PermisologySystem\Services\BaseService;
use Illuminate\Support\Facades\Auth;

class PermissionService extends BaseService
{
    /**
     * Verifica si el usuario tiene permiso para una ruta específica.
     *
     * @param string $route
     * @return bool
     */
    public static function hasAccessToAdminRoute(string $route): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        $permissions = $user->getAllUserPermissions();

        foreach ($permissions as $permission) {
            $manualRoutes = is_string($permission->manual_routes)
                ? json_decode($permission->manual_routes, true)
                : $permission->manual_routes;

            $routeSelectionAdmin = is_string($permission->route_selection_admin)
                ? json_decode($permission->route_selection_admin, true)
                : $permission->route_selection_admin;

            if (isset($routeSelectionAdmin) && in_array($route, $routeSelectionAdmin, true)){
                return true;
            }

            foreach ($manualRoutes as $manualRoute) {
                if (str_ends_with($manualRoute, '/*')) {
                    $prefix = rtrim($manualRoute, '/*');
                    if ($route === $prefix || str_starts_with($route, $prefix . '/')) {
                        return true;
                    }
                } elseif ($manualRoute === $route) {
                    return true;
                }
            }
        }
        return false;
    }
}
