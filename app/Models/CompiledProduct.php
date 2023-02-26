<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class CompiledProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'data', 'processed_product_id', 'stale_level'
    ];

    protected $casts = [
        'data' => 'array'
    ];

    public function stale()
    {
        $this->update(['stale_level' => 1]);
    }

    public function scopeEan(Builder $query, $ean) : Builder
    {
        return $query->where('ean', $ean);
    }

    public function processedProduct() : ?BelongsTo
    {
        return $this->belongsTo(ProcessedProduct::class);
    }

    public function compiler() : BelongsTo
    {
        return $this->belongsTo(Compiler::class);
    }
}