<?php

namespace Tests\Feature\Jobs\ProcessedProduct;

use App\Jobs\Product\Extract;
use App\Jobs\Product\Transform;
use App\Models\Compiler;
use App\Models\ProcessedProduct;
use App\Models\Processor;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TransformTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @return void
     */
    public function can_transform_data_and_set_stale_state()
    {

        $productStructure = [
            'ean' => 'ean',
            'name' => 'string',
            'price' => 'float',
        ];

        $supplier = Supplier::factory()
                        ->config([
                            'root_tag' => 'product',
                            'product_tag' => 'products',
                            'source_type' => 'xls',
                        ])
                        ->productStructure($productStructure)
                        ->hasProducts(1)
                        ->create();

        $compiler = Compiler::factory()
                        ->fields($productStructure)
                        ->create();

        $processor = Processor::factory()
                        ->supplier($supplier)
                        ->compiler($compiler)
                        ->create();

        $processedProduct = ProcessedProduct::factory()
                        ->product($supplier->products()->first())
                        ->processor($processor)
                        ->create();

        [$extractor, $batch] = (new Extract($processedProduct))->withFakeBatch();
        $extractor->handle();

        [$transformer, $batch] = (new Transform($processedProduct))->withFakeBatch();
        $transformer->handle();

        $this->assertDatabaseHas('processed_products', [
            'stale_level' => 0,
        ]);
    }
}
