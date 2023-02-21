<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Export extends Model
{
    use HasFactory;

    protected $fillable = [
        'config', 'path', 'type', 'mappings'
    ];

    protected $casts = [
        'config' => 'array',
        'mappings' => 'array',
    ];

    public function compiler() : BelongsTo
    {
        return $this->belongsTo(Compiler::class);
    }

    public static function boot()
    {
        parent::boot();
        
        self::creating(function($model){
           $model->slug = $model->slug ?? fake()->uuid();
        });
    }
}
