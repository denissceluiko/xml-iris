<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
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

    public function upsertMissing(Collection $EANs)
    {
        $compilerId = $this->id;

        $upserts = $EANs->map(function ($ean) use ($compilerId) {
            return [
                'compiler_id' => $compilerId,
                'ean' => $ean->ean,
            ];
        });

        foreach ($upserts->chunk(500) as $chunk) {
            $this->compiledProducts()->upsert($chunk->toArray(), ['compiler_id', 'ean']);
        }
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
