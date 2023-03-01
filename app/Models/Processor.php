<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;


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

    public function compiledProducts() : HasManyThrough
    {
        return $this->hasManyThrough(CompiledProduct::class, ProcessedProduct::class);
    }

    protected static function booted()
    {
        static::updating(function (Processor $processor) {
            if ($processor->isDirty('mappings')) {
                $processor->processedProducts()->update(['processed_products.stale_level' => 2]);
                $processor->compiledProducts()->update(['compiled_products.stale_level' => 1]);
            } else if ($processor->isDirty('transformations')) {
                $processor->processedProducts()->update(['processed_products.stale_level' => 1]);
                $processor->compiledProducts()->update(['compiled_products.stale_level' => 1]);
            }
        });
    }
}
