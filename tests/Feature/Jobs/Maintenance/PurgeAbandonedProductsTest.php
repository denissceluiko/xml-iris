<?php

namespace Tests\Feature\Jobs\Maintenance;

use App\Jobs\Maintenance\PurgeAbandonedProducts;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Sequence;
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
}
