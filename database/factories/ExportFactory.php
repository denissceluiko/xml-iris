<?php

namespace Database\Factories;

use App\Models\Compiler;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Export>
 */
class ExportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'compiler_id' => Compiler::factory(),
            'type' => 'excel',
            'path' => '',
            'slug' => fake()->uuid(),
            'config' => [
                'root_tag' => 'products',
                'product_tag' => 'product',
            ],
            'mappings' => function ($attributes) {
                return $this->useCompilerMappings($attributes);
            },
        ];
    }

    public function excel() : self
    {
        return $this->state(function ($attributes) {
            return ['type' => 'excel'];
        });
    }

    public function xml() : self
    {
        return $this->state(function ($attributes) {
            return ['type' => 'xml'];
        });
    }


    public function type(string $type) : self
    {
        return $this->state(function ($attributes) use ($type) {
            return ['type' => $type];
        });
    }

    public function mappings(array $mappings) : self
    {
        return $this->state(function ($attributes) use ($mappings) {
            return ['mappings' => $mappings];
        });
    }

    public function path(string $path) : self
    {
        return $this->state(function ($attributes) use ($path) {
            return ['path' => $path];
        });
    }

    public function config(string $config) : self
    {
        return $this->state(function ($attributes) use ($config) {
            return ['config' => $config];
        });
    }

    public function compiler(Compiler $compiler) : self
    {
        return $this->state(function ($attributes) use ($compiler) {
            return ['compiler_id' => $compiler];
        });
    }

    public function useCompilerMappings(array $attributes) : array
    {
        $fields = Compiler::find($attributes['compiler_id'])->fields;
        $mappings = array_combine(array_keys($fields), array_keys($fields));

        return $mappings;
    }
}
