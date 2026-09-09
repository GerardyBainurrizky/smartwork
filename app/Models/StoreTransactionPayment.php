<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class StoreTransactionPayment extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'store_transaction_id', 'amount', 'payment_method', 'payment_date',
        'recorded_by', 'source', 'source_id', 'notes',
        'archived_at', 'data_archive_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'date',
            'archived_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('notArchived', function ($builder) {
            $builder->whereNull($builder->getModel()->getTable() . '.archived_at');
        });

        static::creating(function (StoreTransactionPayment $row) {
            if (empty($row->id)) {
                $row->id = (string) Str::uuid();
            }
        });
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(StoreTransaction::class, 'store_transaction_id')->withoutGlobalScope('notArchived');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function dataArchive(): BelongsTo
    {
        return $this->belongsTo(DataArchive::class);
    }

    public function isNewTransactionPayment(): bool
    {
        if ($this->source === 'initial_payment' || str_contains(mb_strtolower($this->notes ?? ''), 'pembayaran awal')) {
            return true;
        }

        // Relational check: payment created atomically in the same visit session as the new transaction
        if ($this->source === 'visit' && ! empty($this->source_id)) {
            $trx = $this->relationLoaded('transaction') ? $this->transaction : $this->transaction()->first();
            if ($trx && $trx->reference_type === 'visit' && (string) $trx->reference_id === (string) $this->source_id) {
                if ($this->created_at && $trx->created_at && abs($this->created_at->diffInSeconds($trx->created_at)) <= 10) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getPaymentTypeLabelAttribute(): string
    {
        return $this->isNewTransactionPayment()
            ? 'PEMBAYARAN TRANSAKSI BARU'
            : 'PEMBAYARAN PIUTANG LAMA';
    }
}
