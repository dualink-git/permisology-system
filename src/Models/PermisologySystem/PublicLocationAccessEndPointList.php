<?php

namespace PermisologySystem\PermisologySystem\Models\PermisologySystem;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublicLocationAccessEndPointList extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'route_selection_api',
    ];

    protected $casts = [
        'route_selection_api' => 'array',
    ];
}
