<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Route extends Model
{
    use SoftDeletes;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id', 'created_by', 'name', 'date', 'status', 'notes',
        'distance_planned', 'distance_actual',
        'started_at', 'completed_at', 'archived_at', 'data_archive_id',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('notArchived', function ($builder) {
            $builder->whereNull($builder->getModel()->getTable() . '.archived_at');
        });

        static::creating(function (Route $route) {
            if (empty($route->id)) {
                $route->id = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function dataArchive(): BelongsTo
    {
        return $this->belongsTo(DataArchive::class);
    }

    public function getCreatedByLabelAttribute(): string
    {
        if (! $this->creator) {
            return '-';
        }

        if ($this->creator->isAdmin() || $this->creator->isSuperAdmin()) {
            return 'Admin';
        }

        return 'Sales';
    }

    public function stops(): HasMany
    {
        return $this->hasMany(RouteStop::class)->orderBy('sequence');
    }

    public function gpsLocations(): HasMany
    {
        return $this->hasMany(GpsLocation::class);
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Reason about route progress from its stops.
     *
     * A stop counts as "selesai" (processed) when it has either:
     *   - been visited AND its associated visit has been checked out (completed), or
     *   - been skipped (Dilewati).
     *
     * Returns a display status key:
     *   - draft      => 0% selesai        (Menunggu)
     *   - active     => 1% - 99% selesai  (Sedang Berjalan)
     *   - completed  => 100% selesai      (Selesai)
     *   - cancelled  => route was cancelled
     */
    public function getComputedStatusAttribute(): string
    {
        if ($this->status === 'cancelled') {
            return 'cancelled';
        }

        $stops = $this->relationLoaded('stops') ? $this->stops : $this->stops()->get();
        $total = $stops->count();

        if ($total === 0) {
            return $this->status;
        }

        $done = $stops->filter(function ($stop) {
            if ($stop->status === 'skipped') {
                return true;
            }
            if ($stop->status !== 'visited') {
                return false;
            }
            $visit = $stop->relationLoaded('visit') && $stop->visit !== null
                ? $stop->visit
                : ($stop->relationLoaded('anyVisit') ? $stop->anyVisit : $stop->anyVisit()->first());

            return $visit !== null && $visit->status === 'completed';
        })->count();

        if ($done >= $total) {
            return 'completed';
        }

        // Jika route sudah distart (status active di database), maka computed_status = active
        // meskipun belum ada stop yang diproses (done === 0).
        if ($this->status === 'active') {
            return 'active';
        }

        if ($done > 0) {
            return 'active';
        }

        return 'draft';
    }

    /**
     * Count stops that have been fully processed (visited + checked out, or skipped).
     */
    public function processedStopCount(): int
    {
        $stops = $this->relationLoaded('stops') ? $this->stops : $this->stops()->get();

        return $stops->filter(function ($stop) {
            if ($stop->status === 'skipped') {
                return true;
            }
            if ($stop->status !== 'visited') {
                return false;
            }
            $visit = $stop->relationLoaded('visit') && $stop->visit !== null
                ? $stop->visit
                : ($stop->relationLoaded('anyVisit') ? $stop->anyVisit : $stop->anyVisit()->first());

            return $visit !== null && $visit->status === 'completed';
        })->count();
    }

    /**
     * Persist the computed status back to the status column.
     *
     * When every stop has been processed (visited+completed or skipped), the
     * route is automatically marked "completed" so raw-status based queries,
     * statistics and filters stay consistent with the computed display status.
     */
    public function syncStatus(bool $save = true): string
    {
        $computed = $this->computed_status;

        if ($computed === 'completed' && $this->status !== 'completed') {
            $this->status = 'completed';

            if ($this->completed_at === null) {
                $this->completed_at = now();
            }

            if ($save) {
                $this->save();
            }
        }

        return $computed;
    }

    /**
     * Re-evaluate every route and auto-complete those whose stops are all
     * processed (visited+completed or skipped). Used to fix legacy data.
     */
    public static function syncAllStatuses(): int
    {
        $updated = 0;

        Route::with(['stops.visit'])->chunkById(200, function ($routes) use (&$updated) {
            foreach ($routes as $route) {
                if ($route->syncStatus() === 'completed') {
                    $updated++;
                }
            }
        });

        return $updated;
    }

    public function isFinished(): bool
    {
        return $this->computed_status === 'completed';
    }

    /**
     * Build the "Rute Hari Ini" plan for a sales user on a given date.
     *
     * This is the single source of truth shared by the Sales Dashboard and the
     * /route page. It aggregates every route on the same date so that stores
     * added by an Admin for the same date always show up, and computes each
     * stop's status in real time from its visit data.
     *
     * @return array{routes: \Illuminate\Support\Collection, stops: \Illuminate\Support\Collection, date: string, stats: array<string, int>}
     */
    public static function todayPlanFor(int|string $userId, ?string $date = null): array
    {
        $date ??= now()->toDateString();

        $routes = static::with(['stops.store', 'stops.visit'])
            ->where('user_id', $userId)
            ->whereDate('date', $date)
            ->orderBy('created_at')
            ->get();

        $stops = collect();
        foreach ($routes as $route) {
            foreach ($route->stops as $stop) {
                $stops->push($stop);
            }
        }

        return [
            'routes' => $routes,
            'stops' => $stops,
            'date' => $date,
            'stats' => [
                'total' => $stops->count(),
                'menunggu' => $stops->filter(fn ($s) => $s->computed_status === 'pending')->count(),
                'proses' => $stops->filter(fn ($s) => $s->computed_status === 'in_progress')->count(),
                'dikunjungi' => $stops->filter(fn ($s) => $s->computed_status === 'visited')->count(),
                'dilewati' => $stops->filter(fn ($s) => $s->computed_status === 'skipped')->count(),
            ],
        ];
    }
}
