<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Processor extends Model
{
    use HasFactory;

    protected $table = 'processors';

    protected $casts = [
        'mappings' => 'array',
        'transformations' => 'array',
        'last_run_at' => 'datetime',
    ];

    public function compiler() : BelongsTo
    {
        return $this->belongsTo(Compiler::class);
    }

    public function supplier() : BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function processedProducts() : HasMany
    {
        return $this->hasMany(ProcessedProduct::class);
    }

    protected static function booted()
    {
        static::updated(function (Processor $processor) {
            $processor->processedProducts()->update(['stale_level' => 2]);
        });
    }
}
