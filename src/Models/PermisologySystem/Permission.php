<?php

namespace PermisologySystem\PermisologySystem\Models\PermisologySystem;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    // Agregar cast para asegurar que `manual_routes` y `route_selection_admin` se manejen como JSON
    protected $casts = [
        'manual_routes' => 'array',
        'route_selection_admin' => 'array',
        'route_selection_api' => 'array',
        'route_selection_others' => 'array',
    ];

    // Convierte JSON a un array cuando se obtiene de la base de datos
    public function getAllowedRoutesAttribute($value)
    {
        return json_decode($value, true) ?? [];
    }

    // Convierte el array a JSON cuando se guarda en la base de datos
    // Setter para manejar las rutas manuales
    public function setManualRoutesAttribute($value)
    {
        $this->attributes['manual_routes'] = json_encode($value);
    }

    // Setter para manejar las rutas seleccionadas
    public function setrouteSelectionAdminAttribute($value)
    {
        // Asegúrate de que cada ruta seleccionada se almacene como un array con clave 'route'
        $this->attributes['route_selection_admin'] = json_encode($value);
    }

    public function setrouteSelectionApiAttribute($value)
    {
        // Asegúrate de que cada ruta seleccionada se almacene como un array con clave 'route'
        $this->attributes['route_selection_api'] = json_encode($value);
    }

    public function setrouteSelectionOthersAttribute($value)
    {
        // Asegúrate de que cada ruta seleccionada se almacene como un array con clave 'route'
        $this->attributes['route_selection_others'] = json_encode($value);
    }

    // Getter combinado para obtener todas las rutas (manuales y seleccionadas)
    public function getAllRoutesAttribute()
    {
        $manualRoutes = $this->manual_routes ?? [];
        $selectedAdminRoutes = $this->route_selection_admin ?? [];
        $selectedApiRoutes = $this->route_selection_api ?? [];
        $selectedOthersRoutes = $this->route_selection_others ?? [];

        return array_merge($manualRoutes, $selectedAdminRoutes, $selectedApiRoutes, $selectedOthersRoutes);
    }
}
