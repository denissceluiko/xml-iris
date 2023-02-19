<?php

namespace Tests\Feature\Jobs;

use App\Jobs\Product\CacheInvalidateJob;
use App\Jobs\Product\UpsertJob;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class ProductUpsertJobTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @return void
     */
    public function can_insert_product()
    {
        $supplier = $this->makeSupplier();

        $product = Product::factory()
                    ->supplier($supplier)
                    ->make();

        $job = new UpsertJob($supplier, $product->ean, $product->values);
        $job->handle();

        $inserted = Product::where('ean', $product->ean)->first();

        // Can't use assertDatabaseHas due to ->value having JSON in it.
        $this->assertEquals($product->ean, $inserted->ean);
        $this->assertEquals($product->supplier_id, $inserted->supplier_id);
        $this->assertEquals($product->values, $inserted->values);
    }

    /**
     * @test
     * @return void
     */
    public function can_update_product()
    {
        $supplier = $this->makeSupplier();

        $product = Product::factory()
                    ->supplier($supplier)
                    ->make();

        $job = new UpsertJob($supplier, $product->ean, $product->values);
        $job->handle();

        $this->assertDatabaseCount('products', 1);

        $inserted = Product::where('ean', $product->ean)->first();

        // Can't use assertDatabaseHas due to ->value having JSON in it.
        $this->assertEquals($product->ean, $inserted->ean);
        $this->assertEquals($product->supplier_id, $inserted->supplier_id);
        $this->assertEquals($product->values, $inserted->values);

        $newValues = $product->values;
        $newValues['value'][2]['value'] += 5; // Stock +5

        $updateJob = new UpsertJob($supplier, $product->ean, $newValues);
        $updateJob->handle();

        $this->assertDatabaseCount('products', 1);

        $updated = $inserted->fresh();

        $this->assertEquals($product->ean, $updated->ean);
        $this->assertEquals($product->supplier_id, $updated->supplier_id);
        $this->assertEquals($newValues, $updated->values);
    }

    /**
     * @test
     * @return void
     */
    public function will_throw_out_invalid_product()
    {
        $supplier = $this->makeSupplier();

        $product = Product::factory()
                    ->supplier($supplier)
                    ->values([
                        'name' => '{}'.$supplier->config['product_tag'],
                        'value' => '',
                        'attributes' => [],
                    ])
                    ->make();

        $job = new UpsertJob($supplier, $product->ean, $product->values);
        $job->handle();

        $this->assertDatabaseCount('products', 0);
    }

    /**
     * @test
     * @return void
     */
    public function can_dispatches_cache_invalidate_jobs_correctly()
    {
        Bus::fake();

        $supplier = $this->makeSupplier();

        $product = Product::factory()
                    ->supplier($supplier)
                    ->make();

        // No invalidation on insert
        $job = new UpsertJob($supplier, $product->ean, $product->values);
        $job->handle();
        $this->assertDatabaseCount('products', 1);
        Bus::assertNotDispatched(CacheInvalidateJob::class);

        // No invalidation on empty update
        $job = new UpsertJob($supplier, $product->ean, $product->values);
        $job->handle();
        $this->assertDatabaseCount('products', 1);
        Bus::assertNotDispatched(CacheInvalidateJob::class);

        // Invalidate when value updated
        $inserted = Product::where('ean', $product->ean)->first();
        $newValues = $inserted->values;
        $newValues['value'][2]['value'] += 5; // Stock +5

        $updateJob = new UpsertJob($supplier, $product->ean, $newValues);
        $updateJob->handle();
        $this->assertDatabaseCount('products', 1);
        Bus::assertDispatched(CacheInvalidateJob::class);
    }

    public function makeSupplier() : Supplier
    {
        return Supplier::factory()
                    ->config([
                        'root_tag' => 'products',
                        'product_tag' => 'product',
                        'source_type' => 'xml',
                    ])
                    ->productStructure([
                        'ean' => 'ean',
                        'price' => 'float',
                        'stock' => 'int'
                    ])
                    ->create();
    }
}
