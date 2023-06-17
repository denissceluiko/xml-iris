<?php

namespace Tests\Feature\Jobs\Maintenance;

use App\Jobs\Maintenance\PurgeAbandonedProducts;
use App\Models\CompiledProduct;
use App\Models\Compiler;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PurgeAbandonedProductsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @return void
     */
    public function will_purge_abandoned_products()
    {
        $supplier = Supplier::factory()
            ->hasProducts(5)
            ->has(Product::factory()
                ->count(5)
                ->abandoned()
            )
            ->create();

        $this->assertDatabaseCount('products', 10);

        $job = new PurgeAbandonedProducts();
        $job->handle();

        $this->assertDatabaseCount('products', 5);
    }

    /**
     * @test
     * @return void
     */
    public function will_purge_abandoned_compiled_products()
    {
        $compiler = Compiler::factory()
            ->create();

        CompiledProduct::factory()
            ->for($compiler)
            ->processedProduct(null)
            ->count(5)
            ->create();

        $this->assertDatabaseCount('compiled_products', 5);

        $job = new PurgeAbandonedProducts();
        $job->handle();

        $this->assertDatabaseCount('compiled_products', 0);
    }
}
