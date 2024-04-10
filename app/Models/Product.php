<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    static int $abandonedAge = 86400;

    protected $fillable = ['ean', 'values', 'supplier_id', 'last_pulled_at'];

    protected $casts = [
        'values' => 'array',
        'last_pulled_at' => 'datetime',
    ];

    public function isActive() : bool
    {
        if ($this->last_pulled_at === null) return false;
        return Carbon::now()->diffInSeconds($this->last_pulled_at) < self::$abandonedAge;
    }

    public function scopeOrphaned(Builder $query, Carbon $cutoff) : Builder
    {
        return $query->where('last_pulled_at', '<', $cutoff)
                    ->orWhereNull('last_pulled_at');
    }

    public function scopeAbandoned(Builder $query, int $age = null) : Builder
    {
        $age = $age ?? self::$abandonedAge;
        return $query->where('last_pulled_at', '<', Carbon::now()->subSeconds($age))
                    ->orWhereNull('last_pulled_at');
    }

    public function scopeActive(Builder $query, int $age = null) : Builder
    {
        $age = $age ?? self::$abandonedAge;
        return $query->where('last_pulled_at', '>', Carbon::now()->subSeconds($age));
    }

    public function supplier() : BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function processedProducts() : HasMany
    {
        return $this->hasMany(ProcessedProduct::class);
    }
}
