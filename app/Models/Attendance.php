<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Attendance extends Model
{
    use HasFactory;

    const STATUS_PRESENT = 'present';
    const STATUS_LATE = 'late';
    const STATUS_IZIN = 'izin';
    const STATUS_SAKIT = 'sakit';
    const STATUS_CANCELED = 'canceled';
    const STATUS_PENDING = 'pending';

    const ABSENCE_STATUSES = [self::STATUS_IZIN, self::STATUS_SAKIT];

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'date',
        'clock_in',
        'clock_out',
        'clock_in_lat',
        'clock_in_lng',
        'clock_in_address',
        'clock_in_maps_url',
        'clock_in_selfie',
        'clock_out_lat',
        'clock_out_lng',
        'clock_out_address',
        'clock_out_maps_url',
        'clock_out_selfie',
        'status',
        'notes',
        'absence_note',
        'archived_at',
        'data_archive_id',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'clock_in' => 'datetime',
            'clock_out' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('notArchived', function ($builder) {
            $builder->whereNull($builder->getModel()->getTable() . '.archived_at');
        });

        static::creating(function (Attendance $attendance) {
            if (empty($attendance->id)) {
                $attendance->id = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dataArchive(): BelongsTo
    {
        return $this->belongsTo(DataArchive::class);
    }

    public function isCheckedIn(): bool
    {
        return $this->clock_in !== null;
    }

    public function isCheckedOut(): bool
    {
        return $this->clock_out !== null;
    }

    public function isComplete(): bool
    {
        return $this->clock_in && $this->clock_out;
    }

    public function isIzin(): bool
    {
        return $this->status === self::STATUS_IZIN;
    }

    public function isSakit(): bool
    {
        return $this->status === self::STATUS_SAKIT;
    }

    public function isAbsence(): bool
    {
        return in_array($this->status, self::ABSENCE_STATUSES, true);
    }

    public function getIsCanceledAttribute(): bool
    {
        return $this->status === 'canceled';
    }

    public function getCancelledMetaAttribute(): ?array
    {
        if (empty($this->notes)) {
            return null;
        }

        $decoded = json_decode($this->notes, true);

        if (! is_array($decoded) || empty($decoded['cancelled_at'])) {
            return null;
        }

        return $decoded;
    }

    public function getStatusPresensiAttribute(): string
    {
        if ($this->is_canceled) {
            return 'canceled';
        }

        if ($this->status === self::STATUS_IZIN) {
            return 'izin';
        }

        if ($this->status === self::STATUS_SAKIT) {
            return 'sakit';
        }

        if ($this->clock_out !== null) {
            return 'checked_out';
        }

        if ($this->clock_in !== null) {
            return 'checked_in';
        }

        return 'not_present';
    }

    public function getStatusPresensiLabelAttribute(): string
    {
        return match ($this->status_presensi) {
            'canceled' => 'Dibatalkan',
            'checked_out' => 'Check Out',
            'checked_in' => 'Check In',
            'izin' => 'Izin',
            'sakit' => 'Sakit',
            default => 'Belum Presensi',
        };
    }

    public function getStatusPresensiLabelStaffAttribute(): string
    {
        return match ($this->status_presensi) {
            'canceled' => 'Dibatalkan',
            'checked_out' => 'Selesai',
            'checked_in' => 'Check In',
            'izin' => 'Izin',
            'sakit' => 'Sakit',
            default => 'Belum Presensi',
        };
    }
}
