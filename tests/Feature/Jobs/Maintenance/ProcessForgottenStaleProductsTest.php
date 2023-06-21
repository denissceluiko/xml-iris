<?php

namespace Tests\Feature\Jobs\Maintenance;

use App\Jobs\Maintenance\ProcessForgottenStaleProducts;
use App\Models\Compiler;
use App\Models\ProcessedProduct;
use App\Models\Processor;
use App\Models\Supplier;
use Illuminate\Bus\PendingBatch;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class ProcessForgottenStaleProductsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     *
     * @return void
     */
    public function will_dispatch_processor_if_there_are_many_stale_products()
    {
        $compiler = Compiler::factory()->create();
        $supplier = Supplier::factory()->create();

        $processor = Processor::factory()
            ->for($compiler)
            ->for($supplier)
            ->create();

        ProcessedProduct::factory()
            ->for($processor)
            ->count(100)
            ->state(new Sequence(
                ['stale_level' => 2],
                ['stale_level' => 1],
                ['stale_level' => 0],
            ))
            ->create();

        Bus::fake();

        $job = new ProcessForgottenStaleProducts();
        $job->handle();

        Bus::assertBatchCount(1);
        Bus::assertBatched(function (PendingBatch $batch) {
            return $batch->jobs->count() == 1 &&
                   $batch->queue() == 'long-running-queue' &&
                   $batch->name == 'Maintenance processor run';
        });
    }

    /**
     * @test
     *
     * @return void
     */
    public function will_not_dispatch_processor_if_there_are_not_enough_stale_products()
    {
        ProcessedProduct::factory()
            ->count(10)
            ->state(new Sequence(
                ['stale_level' => 2],
            ))
            ->create();

        Bus::fake();

        $job = new ProcessForgottenStaleProducts();
        $job->handle();
        
        Bus::assertNothingDispatched();
    }
}
