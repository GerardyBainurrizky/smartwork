<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class StoreTransaction extends Model
{
    public const STATUS_BELUM_LUNAS = 'BELUM_LUNAS';
    public const STATUS_SEBAGIAN = 'SEBAGIAN';
    public const STATUS_LUNAS = 'LUNAS';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'store_id', 'transaction_code', 'transaction_date', 'description',
        'transaction_amount', 'status', 'reference_type', 'reference_id', 'created_by',
        'archived_at', 'data_archive_id',
    ];

    protected function casts(): array
    {
        return [
            'transaction_amount' => 'decimal:2',
            'transaction_date' => 'date',
            'archived_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('notArchived', function ($builder) {
            $builder->whereNull($builder->getModel()->getTable() . '.archived_at');
        });

        static::creating(function (StoreTransaction $row) {
            if (empty($row->id)) {
                $row->id = (string) Str::uuid();
            }
        });
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class)->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(StoreTransactionPayment::class, 'store_transaction_id');
    }

    public function dataArchive(): BelongsTo
    {
        return $this->belongsTo(DataArchive::class);
    }

    /**
     * Compute total amount paid so far.
     */
    public function getTotalPaidAttribute(): float
    {
        if ($this->relationLoaded('payments')) {
            return (float) $this->payments->sum('amount');
        }

        return (float) $this->payments()->sum('amount');
    }

    /**
     * Compute remaining receivable balance for this transaction.
     */
    public function getRemainingAmountAttribute(): float
    {
        $amount = (float) $this->transaction_amount;
        $paid = $this->total_paid;

        return max(0.0, round($amount - $paid, 2));
    }

    /**
     * Recalculate and persist the transaction's status based on total paid.
     */
    public function recalculateStatus(): string
    {
        $amount = (float) $this->transaction_amount;
        $paid = (float) $this->payments()->sum('amount');
        $remaining = round($amount - $paid, 2);

        if ($remaining <= 0.005) {
            $status = self::STATUS_LUNAS;
        } elseif ($paid > 0.005) {
            $status = self::STATUS_SEBAGIAN;
        } else {
            $status = self::STATUS_BELUM_LUNAS;
        }

        if ($this->status !== $status) {
            $this->update(['status' => $status]);
        }

        return $status;
    }
}
