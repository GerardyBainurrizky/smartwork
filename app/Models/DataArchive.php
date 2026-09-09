<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DataArchive extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'data_type',
        'start_date',
        'end_date',
        'records_count',
        'details',
        'status',
        'notes',
        'archived_at',
        'restored_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'details' => 'array',
            'records_count' => 'integer',
            'archived_at' => 'datetime',
            'restored_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (DataArchive $archive) {
            if (empty($archive->id)) {
                $archive->id = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attendances(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Attendance::class)->withoutGlobalScope('notArchived');
    }

    public function routes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Route::class)->withoutGlobalScope('notArchived');
    }

    public function visits(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Visit::class)->withoutGlobalScope('notArchived');
    }

    public function transactions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StoreTransaction::class)->withoutGlobalScope('notArchived');
    }

    public function transactionPayments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StoreTransactionPayment::class)->withoutGlobalScope('notArchived');
    }

    public function receivables(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StoreReceivable::class)->withoutGlobalScope('notArchived');
    }

    public function getDataTypeLabelAttribute(): string
    {
        return match ($this->data_type) {
            'attendance' => 'Presensi',
            'route' => 'Rencana Kunjungan (Sales)',
            'route_driver' => 'Rencana Pengiriman (Driver)',
            'visit' => 'Kunjungan Sales',
            'visit_driver' => 'Pengiriman (Driver)',
            'transaction' => 'Transaksi',
            'transaction_result' => 'Hasil Transaksi',
            'receivable' => 'Piutang',
            'finance' => 'Keuangan',
            'all' => 'Semua Data Operasional',
            default => ucfirst($this->data_type),
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'archived' => 'Diarsipkan',
            'restored' => 'Dipulihkan (Aktif)',
            'purged' => 'Dihapus Permanen',
            default => ucfirst($this->status),
        };
    }
}
