<?php

namespace Tests\Feature\Jobs;

use App\Jobs\Processor\ProcessProducts;
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
     * Will upsert missing processed products and dispatch master processes that
     * will compile chunks of products to be processed.
     *
     * @test
     * @return void
     */
    public function will_dispatch_product_batch_master_processors()
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
        [$job, $batch] = (new ProcessProducts($processor))->withFakeBatch();

        $job->handle();
        $this->assertDatabaseCount('processed_products', 5);

        Bus::assertBatched(function(PendingBatch $pendingBatch) {
            return  $pendingBatch->name == 'Product processor master' &&
                    $pendingBatch->queue() == 'long-running-queue' &&
                    $pendingBatch->jobs->count() == 1 ;
        });
    }
}
