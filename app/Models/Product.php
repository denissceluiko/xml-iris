<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['ean', 'values', 'supplier_id'];

    protected $casts = [
        'values' => 'array',
    ];

    public function supplier() : BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function processedProducts() : HasMany
    {
        return $this->hasMany(ProcessedProduct::class);
    }
}
