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
            'data' => [],
            'stale_level' => 1,
        ];
    }
}
