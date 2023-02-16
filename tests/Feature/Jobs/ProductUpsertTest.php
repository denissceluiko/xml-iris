<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ProductUpsert;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ProductUpsertTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @return void
     */
    public function can_add_product()
    {
        $supplier = Supplier::factory()
                        ->config([
                            'root_tag' => 'product',
                            'product_tag' => 'products',
                            'source_type' => 'xls',
                        ])
                        ->productStructure([
                            "ean" => "ean"
                        ])
                        ->create();

        $product = Product::factory()->supplier($supplier)->make();

        [$job, $batch] = (new ProductUpsert($supplier->id, $product->ean, $product->values))->withFakeBatch();
        $job->handle();

        $this->assertDatabaseHas('products', [
            'supplier_id' => $supplier->id,
            'ean' => $product->ean,
        ]);
    }

    /**
     * @test
     * @return void
     */
    public function can_update_product()
    {
        $supplier = Supplier::factory()
                        ->config([
                            'root_tag' => 'product',
                            'product_tag' => 'products',
                            'source_type' => 'xls',
                        ])
                        ->productStructure([
                            "ean" => "ean"
                        ])
                        ->create();

        $product = Product::factory()
                    ->supplier($supplier)
                    ->create();

        $this->assertDatabaseHas('products', [
            'supplier_id' => $supplier->id,
            'ean' => $product->ean,
        ]);

        $product->values = [
            "ean" => $product->ean,
            "testing" => "new value",
        ];

        [$job, $batch] = (new ProductUpsert($supplier->id, $product->ean, $product->values))->withFakeBatch();
        $job->handle();

        $product->fresh();

        $this->assertEquals([
            "ean" => $product->ean,
            "testing" => "new value",
        ], $product->values);

        $this->assertDatabaseHas('products', [
            'supplier_id' => $supplier->id,
            'ean' => $product->ean,
        ]);
    }
}
