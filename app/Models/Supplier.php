<?php

namespace App\Models;

use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = ['last_pulled_at', 'pull_interval'];

    protected static $configKeys = [
        'xmlns' => 'optional',
        'root_tag' => 'required',
        'ean_path' => 'required',
        'product_tag' => 'required',
        'source_type' => 'required',
    ];

    protected $casts = [
        'structure' => 'array',
        'config' => 'array',
        'credentials' => 'array',
        'last_pulled_at' => 'datetime',
    ];

    /**
     * Helpers
     *
     */

    public function config(string $key) : string
    {
        return $this->config[$key] ?? '';
    }

    public function configSet(string $key, string $value) : void
    {
        $config = $this->config;
        $config[$key] = $value;
        $this->config = $config;
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
     * Scopes
     *
     */

    public function scopeOutdated(Builder $query)
    {
        return $query->where(function (Builder $query) {
            $query->whereRaw('last_pulled_at < DATE_SUB(NOW(), INTERVAL `pull_interval` SECOND)')
                  ->orWhere('last_pulled_at', null);
        });
    }

    public function scopeActive(Builder $query)
    {
        return $query->where('pull_interval', '>', 0);
    }
     /**
     * Relationships
     *
     */

    public function processors() : HasMany
    {
        return $this->hasMany(Processor::class);
    }

    public function products() : HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function compilers() : BelongsToMany
    {
        return $this->belongsToMany(Compiler::class)->using(Processor::class);
    }
}
