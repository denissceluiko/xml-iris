<?php

namespace Database\Factories;

use App\Models\Processor;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProcessedProduct>
 */
class ProcessedProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'product_id' => Product::factory(),
            'processor_id' => Processor::factory(),
        ];
    }

    public function processor(Processor $processor)
    {
        return $this->state(function ($attributes) use ($processor) {
            return ['processor_id' => $processor];
        });
    }

    public function product(Product $product)
    {
        return $this->state(function ($attributes) use ($product) {
            return ['product_id' => $product];
        });
    }

    public function stale(int $state = 2)
    {
        return $this->state(function ($attributes) use ($state) {
            return ['stale_state' => $state];
        });
    }
}
