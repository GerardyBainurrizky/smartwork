<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GpsLocation extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'route_id', 'route_stop_id', 'latitude', 'longitude',
        'altitude', 'accuracy', 'speed', 'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'altitude' => 'decimal:2',
            'accuracy' => 'decimal:2',
            'speed' => 'decimal:2',
            'recorded_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (GpsLocation $location) {
            if (empty($location->id)) {
                $location->id = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function routeStop(): BelongsTo
    {
        return $this->belongsTo(RouteStop::class);
    }
}