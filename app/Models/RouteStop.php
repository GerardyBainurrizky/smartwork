<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RouteStop extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'route_id', 'store_id', 'sequence', 'estimated_duration_minutes', 'status', 'notes',
    ];

    protected static function booted(): void
    {
        static::creating(function (RouteStop $stop) {
            if (empty($stop->id)) {
                $stop->id = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class)->withTrashed();
    }

    public function visit(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Visit::class);
    }

    public function anyVisit(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Visit::class)->withoutGlobalScope('notArchived');
    }

    /**
     * Display status computed in real time from the actual visit data,
     * not from the stored static status:
     *   - pending     => belum check-in            (Menunggu)
     *   - in_progress => sudah check-in, belum check-out (Proses)
     *   - visited     => sudah check-out           (Dikunjungi)
     *   - skipped     => dilewati                  (Dilewati)
     */
    public function getComputedStatusAttribute(): string
    {
        if ($this->status === 'skipped') {
            return 'skipped';
        }

        $visit = $this->relationLoaded('visit') && $this->visit !== null
            ? $this->visit
            : ($this->relationLoaded('anyVisit') ? $this->anyVisit : $this->anyVisit()->first());

        if ($visit && $visit->check_in_at && $visit->check_out_at) {
            return 'visited';
        }

        if ($visit && $visit->check_in_at) {
            return 'in_progress';
        }

        return 'pending';
    }
}