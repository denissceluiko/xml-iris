<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessedProduct extends Model
{
    use HasFactory;

    protected $fillable = ['extracted_data', 'transformed_data'];

    protected $casts = [
        'extracted_data' => 'array',
        'transformed_data' => 'array',
    ];

    public static $staleness = [
        0   =>  'fresh',
        1   =>  'transformed',
        2   =>  'extracted',
    ];

    public function processor() : BelongsTo
    {
        return $this->belongsTo(Processor::class);
    }

    public function product() : BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function setStale(string $level) : self
    {
        if (!in_array($level, self::$staleness)) return $this;

        $this->stale_level = array_search($level, self::$staleness);
        return $this;
    }
    public function scopeStale(Builder $query, $level = 1) : Builder
    {
        return $query->where('stale_level', '>=', $level);
    }
}
