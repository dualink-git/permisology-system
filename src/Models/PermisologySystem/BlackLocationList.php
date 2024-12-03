<?php

namespace PermisologySystem\PermisologySystem\Models\PermisologySystem;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlackLocationList extends Model
{
    use HasFactory;

    protected $fillable = [
        'blacklist_name',
        'type_address',
        'address',
    ];

    public function ipAddresses()
    {
        return $this->hasMany(self::class, 'blacklist_name', 'blacklist_name')
            ->where('type_address', 'ip');
    }

    // Relación para contar DNS
    public function dnsAddresses()
    {
        return $this->hasMany(self::class, 'blacklist_name', 'blacklist_name')
            ->where('type_address', 'dns');
    }
}
