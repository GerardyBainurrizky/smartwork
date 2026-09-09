<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class StoreReceivable extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'store_id', 'type', 'amount', 'reference_type', 'reference_id',
        'transaction_date', 'payment_method', 'allocation', 'notes', 'created_by',
        'archived_at', 'data_archive_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transaction_date' => 'date',
            'archived_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('notArchived', function ($builder) {
            $builder->whereNull($builder->getModel()->getTable() . '.archived_at');
        });

        static::creating(function (StoreReceivable $row) {
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

    public function dataArchive(): BelongsTo
    {
        return $this->belongsTo(DataArchive::class);
    }
}
