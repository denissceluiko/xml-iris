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
                $mappings = Compiler::find($attributes['compiler_id'])->fields;
                $keys = array_keys($mappings);
                return array_combine($keys, $keys);
            },
            'transformations' => function (array $attributes) {
                $mappings = Compiler::find($attributes['compiler_id'])->fields;
                $keys = array_keys($mappings);
                return array_combine($keys, $keys);
            },
            'enabled' => true,
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

    public function disabled() {
        return $this->state(function ($attributes) {
            return ['enabled' => false];
        });
    }
}
