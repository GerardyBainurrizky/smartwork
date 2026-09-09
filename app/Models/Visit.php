<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Visit extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id', 'route_stop_id', 'route_id', 'store_id', 'status',
        'check_in_at', 'check_in_lat', 'check_in_lng', 'check_in_address', 'check_in_maps_url',
        'storefront_photo', 'check_in_selfie', 'delivered_goods', 'cash_received', 'initial_notes',
        'check_out_at', 'check_out_lat', 'check_out_lng', 'check_out_address', 'check_out_maps_url',
        'visit_result', 'delivered_goods_summary', 'returned_goods', 'transaction_amount', 'payment_method', 'transaction_status',
        'final_notes', 'final_store_photo', 'check_out_selfie', 'archived_at', 'data_archive_id',
    ];

    protected function casts(): array
    {
        return [
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
            'cash_received' => 'decimal:2',
            'transaction_amount' => 'decimal:2',
            'archived_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('notArchived', function ($builder) {
            $builder->whereNull($builder->getModel()->getTable() . '.archived_at');
        });

        static::creating(function (Visit $visit) {
            if (empty($visit->id)) {
                $visit->id = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function routeStop(): BelongsTo
    {
        return $this->belongsTo(RouteStop::class);
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class)->withTrashed();
    }

    public function photos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(VisitPhoto::class);
    }

    public function checkoutPhotos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(VisitPhoto::class)->where('type', 'checkout_documentation');
    }

    public function dataArchive(): BelongsTo
    {
        return $this->belongsTo(DataArchive::class);
    }

    public function payments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StoreTransactionPayment::class, 'source_id');
    }

    public function transactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StoreTransaction::class, 'reference_id');
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isSkipped(): bool
    {
        return ($this->routeStop && $this->routeStop->status === 'skipped')
            || str_starts_with((string) $this->visit_result, 'Dilewati:')
            || $this->status === 'skipped';
    }
}