<?php

namespace Tests\Feature\Jobs\ProcessedProduct;

use App\Jobs\Product\Extract;
use App\Models\Compiler;
use App\Models\ProcessedProduct;
use App\Models\Processor;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ExtractTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @return void
     */
    public function can_extract_data_and_set_stale_state()
    {

        $productStructure = [
            'ean' => 'ean',
            'name' => 'string',
            'price' => 'float',
        ];

        $supplier = Supplier::factory()
                        ->config([
                            'root_tag' => 'products',
                            'product_tag' => 'product',
                            'source_type' => 'xls',
                        ])
                        ->productStructure($productStructure)
                        ->hasProducts(1, [
                            'ean' => '1651160484651',
                        ])
                        ->create();

        $product = $supplier->products()->first();

        $compiler = Compiler::factory()
                        ->fields($productStructure)
                        ->create();

        $processor = Processor::factory()
                        ->for($supplier)
                        ->for($compiler)
                        ->create();

        $processedProduct = ProcessedProduct::factory()
                        ->product($product)
                        ->for($processor)
                        ->create();

        [$extractor, $batch] = (new Extract($processedProduct))->withFakeBatch();
        $extractor->handle();

        $processedProduct->refresh();

        $this->assertEquals($product->ean, $processedProduct->ean);
        $this->assertEquals(1, $processedProduct->stale_level);
        $this->assertEquals([
            '__last_pulled_at' => $product->last_pulled_at->toIsoString(),
        ], $processedProduct->meta_data);
    }
}
