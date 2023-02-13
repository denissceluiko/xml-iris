<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class Processor extends Model
{
    protected $table = 'processors';

    protected $casts = [
        'mappings' => 'array',
        'transformations' => 'array',
    ];

    public function compiler() : BelongsTo
    {
        return $this->belongsTo(Compiler::class);
    }

    public function supplier() : BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
