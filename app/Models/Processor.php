<?php

namespace App\Models;

use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
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
        'enabled' => 'boolean',
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

    public function upsertMissing() : void
    {
        $products = $this->supplier->products()
            ->select('id', 'ean')
            ->whereNotIn('ean', function (Builder $query) {
                $query->select('ean')
                    ->from('processed_products')
                    ->where('processor_id', $this->id);
            })
            ->get();

        $processorId = $this->id;

        $upserts = $products->map(function ($product) use ($processorId) {
            return [
                'product_id' => $product->id,
                'ean' => $product->ean,
                'processor_id' => $processorId,
            ];
        });

        foreach ($upserts->chunk(500) as $chunk) {
            $this->processedProducts()->upsert($chunk->toArray(), ['product_id']);
        }
    }

    public function disabled(): bool
    {
        return !$this->enabled;
    }

    public function scopeEnabled(EloquentBuilder $query): EloquentBuilder
    {
        return $query->where('enabled', true);
    }

    public function scopeDisabled(EloquentBuilder $query): EloquentBuilder
    {
        return $query->where('enabled', false);
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

            if ($processor->isDirty('enabled')) {
                $processor->compiledProducts()->update(['compiled_products.stale_level' => 1]);
            }
        });
    }
}
