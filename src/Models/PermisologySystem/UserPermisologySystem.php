<?php

namespace PermisologySystem\PermisologySystem\Models\PermisologySystem;

use App\Models\User;
use Spatie\Permission\Traits\HasRoles;

class UserPermisologySystem extends User
{
    use HasRoles;

    /**
     * Especifica la tabla 'users' para que no se busque una tabla exclusiva para UserPermisologySystem.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * Define el guard 'web' para la gestión de roles y permisos.
     *
     * @var string
     */
    protected $guard_name = 'web';

    /**
     * Recupera todos los permisos asignados al usuario, incluyendo los heredados a través de roles.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllUserPermissions()
    {
        return $this->getAllPermissions();
    }

    /**
     * Obtiene todas las direcciones DNS asociadas al usuario o a sus roles.
     */
    public function getAllDnsAddresses(): array
    {
        // Obtener todos los roles del usuario
        $roleIds = $this->roles->pluck('id')->toArray();

        // Obtener todos los registros UserLocationAccessEndPointList asociados al usuario o a sus roles
        $userLocationAccessLists = UserLocationAccessEndPointList::where(function ($query) use ($roleIds) {
            $query->where('user_id', $this->id)
                ->orWhereIn('role_id', $roleIds);
        })->get();

        // Combinar todas las direcciones DNS en un solo array
        $dnsAddresses = [];

        foreach ($userLocationAccessLists as $accessList) {
            if (is_array($accessList->dns_addresses)) {
                $dnsAddresses = array_merge($dnsAddresses, $accessList->dns_addresses);
            }
        }

        // Eliminar duplicados y devolver las direcciones DNS
        return array_unique($dnsAddresses);
    }

    /**
     * Obtiene todas las direcciones IP (individuales y rangos CIDR) asociadas al usuario o a sus roles.
     */
    public function getAllIpAddresses(): array
    {
        // Obtener todos los roles del usuario
        $roleIds = $this->roles->pluck('id')->toArray();

        // Obtener todos los registros UserLocationAccessEndPointList asociados al usuario o a sus roles
        $userLocationAccessLists = UserLocationAccessEndPointList::where(function ($query) use ($roleIds) {
            $query->where('user_id', $this->id)
                ->orWhereIn('role_id', $roleIds);
        })->get();

        // Combinar todas las direcciones IP en un solo array
        $ipAddresses = [];

        foreach ($userLocationAccessLists as $accessList) {
            if (is_array($accessList->ip_addresses)) {
                foreach ($accessList->ip_addresses as $ip) {
                    // Validar si es un rango CIDR o una IP única
                    if (self::isValidCidr($ip) || self::isValidIp($ip)) {
                        $ipAddresses[] = $ip;
                    }
                }
            }
        }

        // Eliminar duplicados y devolver las direcciones IP
        return array_unique($ipAddresses);
    }

    /**
     * Verifica si una dirección es un rango CIDR válido.
     *
     * @param  string  $ip
     */
    public static function isValidCidr($ip): bool
    {
        return preg_match('/^(?:\d{1,3}\.){3}\d{1,3}\/\d{1,2}$/', $ip) === 1;
    }

    /**
     * Verifica si una dirección es una IP válida.
     *
     * @param  string  $ip
     */
    public static function isValidIp($ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }
}
