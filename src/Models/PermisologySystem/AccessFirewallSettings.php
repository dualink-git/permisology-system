<?php

// app/Models/AccessFirewallSettings.php

namespace PermisologySystem\PermisologySystem\Models\PermisologySystem;

use Illuminate\Database\Eloquent\Model;

class AccessFirewallSettings extends Model
{
    protected $table = 'access_firewall_settings';

    protected $fillable = [
        'enable_ip_location',
        'enable_dns_location',
        'enable_monitoring_control',
        'enable_unknown_ip_alert',
        'super_main_administrator_id',
        'api_base_path',
    ];

    protected $attributes = [
        'api_base_path' => 'api/',
    ];
}
