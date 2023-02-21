<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Compiler extends Model
{
    use HasFactory;

    protected $casts = [
        'fields' => 'array',
    ];

    public function getFields()
    {
        return $this->fields;
    }

    public function suppliers() : BelongsToMany
    {
        return $this->belongsToMany(Supplier::class)->using(Processor::class);
    }

    public function processors() : HasMany
    {
        return $this->hasMany(Processor::class);
    }

    public function exports() : HasMany
    {
        return $this->hasMany(Export::class);
    }

    public function processedProducts() : HasManyThrough
    {
        return $this->hasManyThrough(ProcessedProduct::class, Processor::class);
    }

    public function compiledProducts() : HasMany
    {
        return $this->hasMany(CompiledProduct::class);
    }
}
