<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model
{
    use SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'code', 'name', 'owner', 'sales_penanggung_jawab_id', 'type', 'address',
        'city', 'kecamatan', 'province',
        'latitude', 'longitude', 'maps_url', 'phone', 'status',
        'is_delivery_destination',
    ];

    protected function casts(): array
    {
        return [
            'is_delivery_destination' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function salesPenanggungJawab(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_penanggung_jawab_id');
    }

    protected static function booted(): void
    {
        static::creating(function (Store $store) {
            if (empty($store->id)) {
                $store->id = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    public function routeStops(): HasMany
    {
        return $this->hasMany(RouteStop::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(StoreTransaction::class);
    }

    public function receivables(): HasMany
    {
        return $this->hasMany(StoreReceivable::class);
    }

    /**
     * Compute total outstanding receivable for this store across all pending transactions.
     */
    public function getTotalReceivableAttribute(): float
    {
        return (float) \App\Services\StoreReceivableService::balanceForStore($this->id);
    }

    /**
     * Get Google Maps search URL:
     * 1. If valid latitude & longitude are available -> use coordinates search.
     * 2. If valid existing maps_url is set -> use maps_url.
     * 3. If address / city / province / name are available -> use urlencoded address query.
     * 4. Otherwise -> return null.
     */
    public function getGoogleMapsUrlAttribute(): ?string
    {
        if ($this->latitude !== null && $this->longitude !== null && is_numeric($this->latitude) && is_numeric($this->longitude)) {
            $lat = (float) $this->latitude;
            $lng = (float) $this->longitude;
            if ($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180 && ($lat != 0 || $lng != 0)) {
                return "https://www.google.com/maps/search/?api=1&query={$lat},{$lng}";
            }
        }

        if (! empty($this->maps_url) && filter_var($this->maps_url, FILTER_VALIDATE_URL)) {
            return $this->maps_url;
        }

        $hasRealAddress = ! empty($this->address) || ! empty($this->kecamatan) || ! empty($this->city) || ! empty($this->province);

        if ($hasRealAddress) {
            $addressParts = array_filter([
                $this->name,
                $this->address,
                $this->kecamatan,
                $this->city,
                $this->province,
            ]);

            if (! empty($addressParts)) {
                $query = implode(', ', $addressParts);
                return 'https://www.google.com/maps/search/?api=1&query=' . urlencode($query);
            }
        }

        return null;
    }
}