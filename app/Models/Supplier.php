<?php

namespace App\Models;

use App\Jobs\SupplierPull;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;

    protected static $configKeys = [
        'xmlns' => 'optional',
        'root_tag' => 'required',
        'product_tag' => 'required',
        'source_type' => 'required',
    ];

    protected $casts = [
        'structure' => 'array',
        'config' => 'array',
        'credentials' => 'array',
    ];

    public function pull() : void
    {
        SupplierPull::dispatch($this);
    }

    /**
     * Helpers
     *
     */


    public function config(string $key) : string
    {
        return $this->config[$key] ?? '';
    }

    public function configKeysSet() : bool
    {
        foreach (self::$configKeys as $key => $value) {
            if ($this->config($key) == '' && $value == 'required') return false;
        }

        return true;
    }

    public static function getConfigKeys() : array
    {
        return self::$configKeys;
    }

    public function getSourceType()
    {
        return $this->config('source_type') == '' ? $this->guessSourceType() : $this->config('source_type');
    }

    protected function guessSourceType() : string|null
    {
        $parts = explode('.', $this->uri);
        $extenstion = strtolower(array_pop($parts));

        if ($extenstion == 'xml')
            return $extenstion;

        return null;
    }

    /**
     * Relationships
     *
     */


    public function products() : HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function compilers() : BelongsToMany
    {
        return $this->belongsToMany(Compiler::class)->using(Processor::class);
    }
}
