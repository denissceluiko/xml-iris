<?php

namespace Database\Factories;

use App\Models\Compiler;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Processor>
 */
class ProcessorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'supplier_id' => Supplier::factory(),
            'compiler_id' => Compiler::factory(),
            'mappings' => function (array $attributes) {
                return Compiler::find($attributes['compiler_id'])->fields;
            },
            'transformations' => function (array $attributes) {
                $mappings = Compiler::find($attributes['compiler_id'])->fields;
                $keys = array_keys($mappings);
                return array_combine($keys, $keys);
            },
        ];
    }

    public function supplier(Supplier $supplier) {
        return $this->state(function ($attributes) use ($supplier) {
            return ['supplier_id' => $supplier];
        });
    }

    public function compiler(Compiler $compiler) {
        return $this->state(function ($attributes) use ($compiler) {
            return ['compiler_id' => $compiler];
        });
    }
}
