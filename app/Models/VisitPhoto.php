<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class VisitPhoto extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'visit_id',
        'type',
        'photo_path',
        'caption',
    ];

    protected static function booted(): void
    {
        static::creating(function (VisitPhoto $photo) {
            if (empty($photo->id)) {
                $photo->id = (string) Str::uuid();
            }
        });
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }
}
