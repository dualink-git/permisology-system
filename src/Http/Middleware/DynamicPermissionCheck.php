<?php


namespace PermisologySystem\PermisologySystem\Http\Middleware;


use Closure;
use PermisologySystem\PermisologySystem\Services\RouteService;
use Illuminate\Support\Facades\Auth;
use PermisologySystem\PermisologySystem\Models\PermisologySystem\BlackLocationList;
use PermisologySystem\PermisologySystem\Models\PermisologySystem\AccessFirewallSettings;
use PermisologySystem\PermisologySystem\Models\PermisologySystem\UserLocationAccessEndPointList;
use PermisologySystem\PermisologySystem\Models\PermisologySystem\PublicLocationAccessEndPointList;


class DynamicPermissionCheck
{
    public function handle($request, Closure $next)
    {
        try {

            $currentRoute = $request->path();
            $currentApiRoute = null;
            $userDns = gethostbyaddr($request->ip());
            $settings = AccessFirewallSettings::first();
            $apiBasePath = $settings->api_base_path;

            if (str_starts_with($request->path(), $apiBasePath)) {
                $currentApiRoute = "{$request->method()}-/{$request->path()}";
            }
            if (str_starts_with($request->path(), $apiBasePath)) {
                $currentRoute = RouteService::formatCurrentRoute($request);
            }
            $userIp = $request->ip();

            if (($settings->enable_ip_location || $settings->enable_dns_location)) {
                $this->checkBlackList($settings, $userIp, $request, $apiBasePath);
            }

            if ($this->checkIsFreeRoute($currentRoute, $request->path())) {
                return $next($request);
            }

            if (str_starts_with($request->path(), $apiBasePath)) {
                if ($this->checkPublicEndPoints($currentApiRoute)) {
                    return $next($request);
                }
            }

            if (!str_starts_with($request->path(), $apiBasePath)) {
                if (Auth::check()) {
                    $user = Auth::user();
                    if ($this->checkUserPermissions($user, $currentRoute, $next, $request)) {
                        return $next($request);
                    }
                } else {
                    redirect()->route(RouteService::getLoginPath());
                }
            } else if (Auth::guard('sanctum')->check()) {
                $user = Auth::guard('sanctum')->user();
                $enableDNS = false;
                $enableIp = false;
                if ($settings->enable_dns_location) {

                    if ($userDns === $userIp || $userDns === false) {
                        $enableDNS = false;
                    } else {
                        $allowedDnsAddresses = $user->getAllDnsAddresses();
                        if (!$this->validateDNSPermission($userDns, $allowedDnsAddresses)) {
                            $enableDNS = false;
                        } else {
                            $enableDNS = true;
                        }
                    }
                }
                if ($settings->enable_ip_location && !$enableDNS) {
                    $allowedIpAddresses = $user->getAllIpAddresses();
                    if (!self::validateIpPermissions($userIp, $allowedIpAddresses)) {
                        $this->reportDontHavePermission($request, $apiBasePath);
                    } else {
                        $enableIp = true;
                    }
                }

                if (
                    $this->checkUserApiPermissions($user, $currentApiRoute, $next, $request, $settings)
                    && ($enableDNS || $enableIp)
                ) {
                    return $next($request);
                }
            }

            $this->reportDontHavePermission($request, $apiBasePath);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            if ($request->expectsJson() || str_starts_with($request->path(), $apiBasePath)) {
                return response()->json([
                    'error' => $e->getMessage()
                ], $e->getStatusCode());
            }
            throw $e;
        }
    }

    private function checkBlackList(AccessFirewallSettings $settings, string $userIp, $request, $apiBasePath)
    {
        if ($settings && $settings->enable_ip_location) {
            // Obtener las direcciones IP de la lista negra
            $blacklistedIps = BlackLocationList::where('type_address', 'ip')
                ->pluck('address')
                ->toArray();

            // Verificar si la IP del usuario está en la lista negra
            if (in_array($userIp, $blacklistedIps, true)) {
                $this->reportDontHavePermission($request, $apiBasePath);
                return; // Termina la ejecución si la IP está en la lista negra
            }
        }

        if ($settings && $settings->enable_dns_location) {
            // Obtener los DNS de la lista negra
            $blacklistedDns = BlackLocationList::where('type_address', 'dns')
                ->pluck('address')
                ->toArray();

            // Verificar si la IP del usuario está en la lista negra de DNS
            if (in_array($userIp, $blacklistedDns, true)) {
                $this->reportDontHavePermission($request, $apiBasePath);
            }
        }
    }


    private function checkPublicEndPoints($currentApiRoute)
    {
        $publicEndpoints = PublicLocationAccessEndPointList::all();
        [$currentMethod, $currentPath] = explode('-', $currentApiRoute, 2);
        $currentPath = ltrim($currentPath, '/');

        foreach ($publicEndpoints as $endpoint) {
            $routeSelectionApi = $endpoint->route_selection_api;
            foreach ($routeSelectionApi as $route) {
                // Obtiene el método y el path de la ruta guardada en publicEndpoints
                [$routeMethods, $routePath] = explode('-', $route, 2);
                $methods = strpos($routeMethods, '|') !== false
                    ? explode('|', $routeMethods)
                    : [$routeMethods];

                $routePathPattern = preg_replace('/\{[^}]+\}/', '\d+', $routePath);
                $routePathPattern = str_replace('/', '\/', $routePathPattern); // Convertir la ruta en un patrón de regex
                $routePathPattern = '/^' . $routePathPattern . '$/';

                if (in_array($currentMethod, $methods) && preg_match($routePathPattern, $currentPath)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function checkEndPoint($endpoints, $currentMethod, $currentPath)
    {
        $currentPath = ltrim($currentPath, '/');

        foreach ($endpoints as $route) {
            [$routeMethods, $routePath] = explode('-', $route, 2);

            $methods = strpos($routeMethods, '|') !== false
                ? explode('|', $routeMethods)
                : [$routeMethods];

            $routePathPattern = preg_replace('/\{[^}]+\}/', '\d+', $routePath);
            $routePathPattern = str_replace('/', '\/', $routePathPattern);
            $routePathPattern = '/^' . $routePathPattern . '$/';

            if (in_array($currentMethod, $methods) && preg_match($routePathPattern, $currentPath)) {
                return true;
            }
        }
        return false;
    }

    private function checkIsFreeRoute($currentRoute, $currentPath)
    {
        $adminRoute = RouteService::getAdminRoute();
        $loginPath = RouteService::getLoginPath();

        if (
            $currentRoute === "$adminRoute/login"
            || $currentRoute === "$adminRoute/logout"
            || ($currentRoute === "$adminRoute" && !Auth::check())
            || $currentRoute === $loginPath
            || $currentRoute === ''
            || $currentPath === 'api/login'
        ) {
            return true;
        }

        return false;
    }

    private function checkUserPermissions($user, $currentRoute, $request)
    {
        $permissions = $user->getAllUserPermissions();
        $hasPermissionFlag = false;

        $permissionIndex = 0;
        while (!$hasPermissionFlag && $permissionIndex < count($permissions)) {
            $permission = $permissions[$permissionIndex];
            $permissionIndex++;

            $manualRoutes = is_string($permission->manual_routes)
                ? json_decode($permission->manual_routes, true)
                : $permission->manual_routes;

            $routeSelectionAdmin = is_string($permission->route_selection_admin)
                ? json_decode($permission->route_selection_admin, true)
                : $permission->route_selection_admin;

            $routeSelectionOthers = is_string($permission->route_selection_others)
                ? json_decode($permission->route_selection_others, true)
                : $permission->route_selection_others;


            if (
                (isset($routeSelectionAdmin) && in_array($currentRoute, $routeSelectionAdmin, true))
                || (isset($routeSelectionOthers) && in_array($currentRoute, $routeSelectionOthers, true))
            ) {
                $hasPermissionFlag = true;
                break;
            }

            if (empty($manualRoutes)) {
                break;
            }

            $manualRouteIndex = 0;
            while (!$hasPermissionFlag && $manualRouteIndex < count($manualRoutes)) {
                $manualRoute = $manualRoutes[$manualRouteIndex];
                $manualRouteIndex++;
                if (is_string($manualRoute) && str_ends_with($manualRoute, '/*')) {
                    $prefix = rtrim($manualRoute, '/*');
                    if ($currentRoute === $prefix || str_starts_with($currentRoute, $prefix . '/')) {
                        $hasPermissionFlag = true;
                        break;
                    }
                } elseif ($manualRoute === $currentRoute) {
                    $hasPermissionFlag = true;
                    break;
                }
            }
        }

        return $hasPermissionFlag;
    }

    private function checkUserApiPermissions($user, $currentRoute, $method, $request, $settings)
    {
        $permissions = $user->getAllUserPermissions();
        $hasPermissionFlag = false;

        $permissionIndex = 0;
        while (!$hasPermissionFlag && $permissionIndex < count($permissions)) {
            $permission = $permissions[$permissionIndex];
            $permissionIndex++;

            $routeSelectionApi = is_string($permission->route_selection_api)
                ? json_decode($permission->route_selection_api, true)
                : $permission->route_selection_api;

            $currentApiRoute = "{$request->method()}-/{$request->path()}";
            [$currentMethod, $currentPath] = explode('-', $currentApiRoute, 2);
            if (self::checkEndPoint($routeSelectionApi, $currentMethod, $currentPath)) {
                $hasPermissionFlag = true;
                break;
            }
        }
        return $hasPermissionFlag;
    }


    /**
     * Valida si el DNS del usuario está permitido según las reglas especificadas.
     *
     * @param string $userDns El DNS del usuario, obtenido a partir de su IP
     * @param array $allowedDnsAddresses Lista de DNS permitidos
     * @return bool
     */
    private static function validateDNSPermission($userDns, array $allowedDnsAddresses): bool
    {
        // Si '*' está en la lista de direcciones DNS permitidas, permitir todo
        if (in_array('*', $allowedDnsAddresses, true)) {
            return true;
        }

        foreach ($allowedDnsAddresses as $pattern) {
            // Convertir el patrón de DNS en una expresión regular
            $regex = self::dnsPatternToRegex($pattern);

            // Verificar si el DNS del usuario coincide con el patrón
            if (preg_match($regex, $userDns)) {
                return true;
            }
        }

        // Si no se encuentra coincidencia, denegar el acceso
        return false;
    }

    /**
     * Convierte un patrón de DNS en una expresión regular.
     *
     * @param string $pattern El patrón de DNS, como *.monitos.com o monitos.*
     * @return string La expresión regular
     */
    private static function dnsPatternToRegex($pattern): string
    {
        // Escapar caracteres especiales excepto '*'
        $escapedPattern = preg_quote($pattern, '#');

        // Reemplazar '*' por un patrón de regex que permita cualquier carácter válido de dominio
        $regex = str_replace('\*', '[a-zA-Z0-9.-]+', $escapedPattern);

        // Asegurarse de que la expresión regular se aplique al dominio completo
        return '#^' . $regex . '$#';
    }

    private function reportDontHavePermission($request, $apiBasePath)
    {
        if (str_starts_with($request->path(), $apiBasePath)) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(404, 'Unauthorized access');
        }
        abort(403, 'Unauthorized access');
    }

    /**
     * Valida si la IP del usuario está permitida según las reglas especificadas.
     *
     * @param string $userIp La dirección IP del usuario
     * @param array $allowedIpAddresses Lista de direcciones IP o rangos CIDR permitidos
     * @return bool
     */
    private static function validateIpPermissions($userIp, array $allowedIpAddresses): bool
    {
        // Si '*' está en la lista de direcciones IP permitidas, permitir cualquier IP
        if (in_array('*', $allowedIpAddresses, true)) {
            return true;
        }

        foreach ($allowedIpAddresses as $ipPattern) {
            // Si es una IP exacta, hacer comparación directa
            if (self::isValidIp($ipPattern) && $userIp === $ipPattern) {
                return true;
            }

            // Verificar si el patrón es un rango CIDR (ej., 192.168.1.0/24)
            if (self::isValidCidr($ipPattern) && self::isCidrMatch($userIp, $ipPattern)) {
                return true;
            }
        }

        // Si no se encuentra coincidencia, denegar el acceso
        return false;
    }

    /**
     * Comprueba si una dirección IP está dentro de un rango CIDR.
     *
     * @param string $ip La dirección IP del usuario
     * @param string $cidr El rango CIDR (ej., 192.168.1.0/24)
     * @return bool
     */
    private static function isCidrMatch($ip, $cidr): bool
    {
        // Dividir el CIDR en la IP base y el prefijo de red
        list($subnet, $prefix) = explode('/', $cidr);

        // Convertir la IP en binario
        $ipBinary = ip2long($ip);
        $subnetBinary = ip2long($subnet);

        // Calcular la máscara de red
        $mask = -1 << (32 - (int)$prefix);

        // Comparar la IP con la subred
        return ($ipBinary & $mask) === ($subnetBinary & $mask);
    }

    /**
     * Verifica si una dirección es una IP válida.
     *
     * @param string $ip
     * @return bool
     */
    private static function isValidIp($ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Verifica si una dirección es un rango CIDR válido.
     *
     * @param string $ip
     * @return bool
     */
    private static function isValidCidr($ip): bool
    {
        return preg_match('/^(?:\d{1,3}\.){3}\d{1,3}\/\d{1,2}$/', $ip) === 1;
    }
}
