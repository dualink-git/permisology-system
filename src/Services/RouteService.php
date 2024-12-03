<?php

namespace PermisologySystem\PermisologySystem\Services;

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Route;
use PermisologySystem\PermisologySystem\Models\PermisologySystem\AccessFirewallSettings;

class RouteService
{

    public static function getAdminRoute()
    {
        return Filament::getCurrentPanel()?->getPath() ?? 'admin';
    }

    public static function getLoginPath()
    {
        if (Route::has('login')) {
            return route('login', [], false);
        }
        return '/';
    }

    /**
     * Obtiene todas las rutas disponibles filtradas opcionalmente por un prefijo.
     *
     * @param string|null $prefix Prefijo opcional para filtrar rutas.
     * @return array Lista de rutas en formato [ 'uri' => 'uri' ].
     */
    public static function getAllRoutes(string $prefix = null): array
    {
        $routes = Route::getRoutes();

        $routeList = [];

        foreach ($routes as $route) {
            $uri = $route->uri();
            // Filtra por prefijo si se proporciona
            if ($prefix === null || str_starts_with($uri, $prefix)) {
                $routeList[$uri] = $uri;
            }
        }

        return $routeList;
    }

    public static function getNotRoutes(array $excludedPrefixes = []): array
    {
        // Obtén todas las rutas definidas en la aplicación
        $routes = Route::getRoutes();
        $routeList = [];

        foreach ($routes as $route) {
            $uri = $route->uri();

            // Verifica si la URI no comienza con ninguno de los prefijos excluidos
            $shouldExclude = false;
            foreach ($excludedPrefixes as $prefix) {
                if (str_starts_with($uri, $prefix)) {
                    $shouldExclude = true;
                    break;
                }
            }

            // Si no se debe excluir, añade la ruta a la lista
            if (!$shouldExclude) {
                $routeList[$uri] = $uri;
            }
        }

        return $routeList;
    }

    public static function getFormattedApiRoutes()
    {
        $settings = AccessFirewallSettings::first();
        $apiBasePath =  'api/';
        if ($settings) {
            $apiBasePath = $settings?->api_base_path ?? 'api/';
        }

        $routes = collect(Route::getRoutes())
            ->filter(function ($route) use ($apiBasePath) {
                return strpos($route->uri, $apiBasePath) === 0;
            })
            ->mapWithKeys(function ($route) {
                $methods = implode('|', $route->methods());
                $uri = $route->uri();
                $formattedRoute = "{$methods}-{$uri}";

                return [$formattedRoute => $formattedRoute];
            });
        return $routes;
    }


    public static function formatCurrentRoute($request)
    {
        $route = $request->route();
        if ($route && $route->getName()) {
            return $route->getName();
        }

        if ($route && $route->getActionName()) {
            return $route->getActionName();
        }

        return "{$request->method()}-{$request->path()}";
    }
}
