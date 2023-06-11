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
    public function definition() : array
    {
        return [
            'ean' => fake()->ean13(),
            'product_id' => Product::factory(),
            'processor_id' => Processor::factory(),
            'stale_level' => 2,
            'extracted_data' => [],
            'transformed_data' => [],
            'meta_data' => [],
        ];
    }

    public function processor(?Processor $processor) : self
    {
        return $this->state(function ($attributes) use ($processor) {
            return ['processor_id' => $processor];
        });
    }

    public function product(?Product $product, bool $includeMeta = false) : self
    {
        return $this->state(function ($attributes) use ($product, $includeMeta) {
            return [
                'ean' => $product->ean,
                'product_id' => $product,
                'meta_data' => $includeMeta ? $this->formatMetaData($product) : [],
            ];
        });
    }

    public function extractedData(array $data) : self
    {
        return $this->state(function ($attributes) use ($data) {
            return ['extracted_data' => $data];
        });
    }

    public function transformedData(array $data) : self
    {
        return $this->state(function ($attributes) use ($data) {
            return ['transformed_data' => $data];
        });
    }

    public function stale(int $state = 2) : self
    {
        return $this->state(function ($attributes) use ($state) {
            return ['stale_level' => $state];
        });
    }

    public function fresh() : self
    {
        return $this->stale(0);
    }

    public function formatMetaData(?Product $product) : array
    {
        if (! $product instanceof Product) return [];

        return [
            '__last_pulled_at' => $product->last_pulled_at,
        ];
    }
}
