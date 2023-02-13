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
                        ->uri('this-file-does-not-exist.xml')
                        ->config([])
                        ->structure([
                            "ean" => "ean"
                        ])
                        ->create();

        $product = Product::factory()->generateValues($supplier->structure, $supplier->config)->make();

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
                        ->uri('this-file-does-not-exist.xml')
                        ->config([])
                        ->structure([
                            "ean" => "ean"
                        ])
                        ->create();

        $product = Product::factory()
                    ->supplier($supplier)
                    ->generateValues($supplier->structure, $supplier->config)
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
