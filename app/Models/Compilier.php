<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Compilier extends Model
{
    use HasFactory;

    public function suppliers() : BelongsToMany
    {
        return $this->belongsToMany(Supplier::class);
    }
}
