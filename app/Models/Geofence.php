<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Geofence extends Model
{
    protected $fillable = [
        'name',
        'polygon_geojson',
        'is_active',
    ];

    protected $casts = [
        'polygon_geojson' => 'array',
        'is_active' => 'boolean',
    ];
}
