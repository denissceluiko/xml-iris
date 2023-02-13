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

    protected $requiredConfigKeys = [
        'root_tag',
        'product_tag',
        'source_type',
    ];

    protected $casts = [
        'structure' => 'array',
        'config' => 'array',
    ];

    public function pull() : void
    {
        if (!$this->canPull()) return;

        SupplierPull::dispatch($this);
    }

    /**
     * Helpers
     *
     */


    protected function config(string $key) : string
    {
        return $this->config[$key] ?? '';
    }

    public function canPull() : bool
    {
        if (empty($this->uri)) return false;
        if (!$this->configKeysSet()) return false;
        if (!is_array($this->structure)) return false;

        return true;
    }

    protected function configKeysSet() : bool
    {
        foreach ($this->requiredConfigKeys as $key) {
            if ($this->config($key) == '') return false;
        }

        return true;
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
