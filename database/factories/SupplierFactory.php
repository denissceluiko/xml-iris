<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Supplier>
 */
class SupplierFactory extends Factory
{

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => fake()->words(2, true),
            'uri' => function($attributes) {
                return $this->getUri($attributes);
            },
            'config' => function ($attributes) {
                return $this->getConfig($attributes);
            },
            'structure' => function ($attributes) {
                return $this->getStructure($attributes);
            },
        ];
    }

    public function uri(string $uri) : self
    {
        return $this->state(function (array $attributes) use ($uri) {
            return ['uri' => $uri];
        });
    }

    public function config(array $config) : self
    {
        return $this->state(function (array $attributes) use ($config) {
            return ['config' => $config];
        });
    }

    public function credentials(string $login, string $password) : self
    {
        return $this->state(function (array $attributes) use ($login, $password) {
            return ['credentials' => [
                'login' => $login,
                'password' => $password,
            ]];
        });
    }

    public function structure(array $structure) : self
    {
        return $this->state(function (array $attributes) use ($structure) {
            return ['structure' => $structure];
        });
    }

    public function productStructure(array $structure) : self
    {
        return $this->state(function (array $attributes) use ($structure) {
            return ['structure' => $this->createStructure($attributes, $structure)];
        });
    }

    public function makeFields(int $count) : self
    {
        return $this->state(function (array $attributes) use ($count) {
            return ['makeable_field_count' => $count];
        });
    }

    protected function createConfig(array $attributes) : array
    {
        $productTag = fake()->word();

        $config = [
            'xmlns' => '',
            'ean_path' => 'ean',
            'root_tag' => $productTag.'s',
            'product_tag' => $productTag,
            'source_type' => $this->getSourceType($attributes),
        ];

        return array_merge(Supplier::getConfigKeys(), $config);
    }

    protected function getSourceType(array $attributes)
    {
        if (empty($attributes['uri'])) return '';

        $parts = explode('.', $attributes['uri']);
        $ext = array_pop($parts);

        return $ext;
    }

    protected function getConfig(array $attributes) : array
    {
        return $this->createConfig($attributes);
    }

    protected function createStructure(array $attributes, array $structure = []) : array
    {
        if (empty($attributes['config'])
            || empty($attributes['config']['root_tag'])
            || empty($attributes['config']['product_tag'])) {
                return [];
        }

        $structure = [
            $attributes['config']['root_tag'] => [
                "type" => "repeatingElements",
                "child" => $attributes['config']['product_tag'],
                "value" => [
                    "type" => "keyValue",
                    "value" => empty($structure) ? $this->createFields($attributes) : $structure,
                ]
            ],
        ];

        return $structure;
    }

    protected function createFields($attributes) : array
    {
        $fields = [];
        $count = $attributes['makeable_field_count'] ?? 0;

        for ($i=0; $i<$count; $i++) {
            $fields[fake()->word()] = "string";
        }

        return $fields;
    }

    protected function getStructure(array $attributes) : array
    {
        return $attributes['structure_initial'] ?? $this->createStructure($attributes);
    }

    protected function getUri($attributes) : string
    {
        return is_string($attributes['uri']) ? $attributes['uri'] : 'this_file_does_not_exist.xml';
    }
}
