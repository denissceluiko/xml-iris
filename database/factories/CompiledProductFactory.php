<?php

namespace Database\Factories;

use App\Models\Compiler;
use App\Models\ProcessedProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CompiledProduct>
 */
class CompiledProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'ean' => fake()->ean13(),
            'processed_product_id' => ProcessedProduct::factory(),
            'compiler_id' => Compiler::factory(),
            'data' => null,
            'stale_level' => 1,
        ];
    }

    public function compiler(?Compiler $compiler) : self
    {
        return $this->state(function ($attributes) use ($compiler) {
            return ['compiler_id' => $compiler];
        });
    }

    public function data(array $data) : self
    {
        return $this->state(function ($attributes) use ($data) {
            return ['data' => $data];
        });
    }

    public function stale(int $state = 1) : self
    {
        return $this->state(function ($attributes) use ($state) {
            return ['stale_level' => $state];
        });
    }

    public function fresh() : self
    {
        return $this->stale(0);
    }
}
