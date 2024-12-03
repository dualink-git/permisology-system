<?php

namespace PermisologySystem\PermisologySystem\Models\PermisologySystem;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class UserLocationAccessEndPointList extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'association_type',
        'user_id',
        'role_id',
        'ip_addresses',
        'dns_addresses',
    ];

    protected $casts = [
        'route_selection_api' => 'array',
        'ip_addresses' => 'array',
        'dns_addresses' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(UserPermisologySystem::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Devuelve las direcciones DNS asociadas al usuario o al rol.
     */
    public function getDnsAddresses(): array
    {
        // Devuelve las direcciones DNS si están configuradas, o un array vacío si no lo están
        return $this->dns_addresses ?? [];
    }
}
