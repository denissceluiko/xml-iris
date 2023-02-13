<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
}
