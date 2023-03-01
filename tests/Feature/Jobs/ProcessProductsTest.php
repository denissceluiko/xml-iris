<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ProcessProducts;
use App\Models\Compiler;
use App\Models\Processor;
use App\Models\Supplier;
use Illuminate\Bus\PendingBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class ProcessProductsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @return void
     */
    public function will_dispatch_products_processing()
    {

        $compiler = Compiler::factory()
                        ->fields([
                            'ean' => 'ean',
                            'name' => 'string',
                            'price' => 'float',
                        ])
                        ->create();

        $supplier = Supplier::factory()
                        ->config([
                            'root_tag' => 'product',
                            'product_tag' => 'products',
                            'source_type' => 'xls',
                        ])
                        ->productStructure($compiler->fields)
                        ->hasProducts(5)
                        ->create();

        $processor = Processor::factory()
                        ->supplier($supplier)
                        ->compiler($compiler)
                        ->create();

        Bus::fake();
        $job = new ProcessProducts($processor);

        $job->handle();
        $this->assertDatabaseCount('processed_products', 5);

        Bus::assertBatched(function(PendingBatch $batch) {
            return $batch->jobs->count() == 1 ;
        });
    }
}
