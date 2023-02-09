<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Http;
use Sabre\Xml\Reader;

class Supplier extends Model
{
    use HasFactory;

    protected $casts = [
        'structure' => 'array',
        'config' => 'array',
    ];

    public function pull() : array
    {
        $service = new \Sabre\Xml\Service();
        $service->elementMap = $this->generateElementMap();

        // foreach ($service->parse($this->getFileContents()) as $key => $row)
        // {
        //     $product = $this->resolve($row);
        //     $this->products()->updateOrCreate($product);
        // }

        return $service->parse($this->getFileContents());
    }

    protected function getFileContents() : string
    {
        if (file_exists($this->uri))
            return  file_get_contents($this->uri);

        $response = Http::get($this->uri);

        return $response->ok() ? $response->body() : null;
    }

    protected function generateElementMap() : array
    {
        $map = [];

        $map = array_merge($this->resolveFields($this->xmlns($this->config('product_tag')), $this->structure), $map);

        return $map;
    }

    protected function resolveFields(string $parent, array $fields) : array
    {
        $map = [];

        foreach ($fields as $name => $field) {
            if (!is_array($field) || !isset($field['type'])) continue;

            if ($field['type'] == 'repeatingElements')
            {
                $map[$this->xmlns($name)] = function (Reader $reader) use ($field) {
                    return \Sabre\Xml\Deserializer\repeatingElements($reader, $field['child']);
                };
            } else if ($field['type'] == 'keyValue')
            {
                $map[$this->xmlns($name)] = function (Reader $reader) {
                    return \Sabre\Xml\Deserializer\keyValue($reader, $this->config('xmlns'));
                };
            }
        }

        return $map;
    }

    protected function xmlns(string $selector = '') : string
    {
        return '{'.$this->config('xmlns').'}'.$selector;
    }

    protected function config(string $key) : string
    {
        return $this->config[$key] ?? '';
    }

    public function products() : HasMany
    {
        return $this->hasMany(Product::class);
    }
}
