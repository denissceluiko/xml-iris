<?php

namespace Tests\Feature\Jobs;

use App\Jobs\Processor\ProcessProducts;
use App\Jobs\SupplierPull;
use App\Jobs\UpdateCycle;
use App\Models\Compiler;
use App\Models\Processor;
use App\Models\Supplier;
use Illuminate\Bus\PendingBatch;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class UpdateCycleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @return void
     */
    public function will_prepare_and_dispatch_update_cycle()
    {
        Bus::fake();

        $compiler = Compiler::factory()
            ->interval(3600)
            ->create();

        Processor::factory()
            ->for($compiler)
            ->for(Supplier::factory()
                ->pullInterval(3600)
                ->pulledAt(now()->subHours(2))
            )
            ->create();

        Processor::factory()
            ->for($compiler)
            ->for(Supplier::factory()
                ->pullInterval(3600)
                ->pulledAt(now()->subHours(2))
            )
            ->create();

        $this->assertEquals(2, Supplier::active()->count());

        $job = new UpdateCycle();
        $job->handle();

        Bus::assertBatchCount(1);
        Bus::assertBatched(function (PendingBatch $batch) {
            return $batch->name == "Update Cycle" &&
                   $batch->queue() == 'long-running-queue' &&
                   $batch->jobs->count() == 2 &&
                   $batch->jobs[0][0] instanceof SupplierPull &&
                   $batch->jobs[0][1] instanceof ProcessProducts &&
                   $batch->jobs[1][0] instanceof SupplierPull &&
                   $batch->jobs[1][1] instanceof ProcessProducts;
        });
    }

    /**
     * @test
     * @return void
     */
    public function will_not_dispatch_update_cycle_if_nothing_has_to_be_updated()
    {
        Bus::fake();

        $compiler = Compiler::factory()
            ->create();

        Processor::factory()
            ->for($compiler)
            ->for(Supplier::factory()
                ->pullInterval(3600)
                ->pulledAt(now())
            )
            ->create();

        $this->assertEquals(1, Supplier::active()->count());

        $job = new UpdateCycle();
        $job->handle();

        Bus::assertNothingBatched();
    }

    /**
     * @test
     * @return void
     */
    public function will_dispatch_product_processing_for_processor_only_if_its_compiler_is_active()
    {
        Bus::fake();

        // Outdated supplier
        $supplier = Supplier::factory()
            ->pullInterval(3600)
            ->pulledAt(now()->subMinutes(61))
            ->create();

        // Inactive compiler
        $compilerInactive = Compiler::factory()
            ->interval(0)
            ->create();

        Processor::factory()
            ->for($compilerInactive)
            ->for($supplier)
            ->create();

        // Active compiler
        $compilerActive = Compiler::factory()
            ->interval(3600)
            ->create();

        Processor::factory()
            ->for($compilerActive)
            ->for($supplier)
            ->create();

        $job = new UpdateCycle();
        $job->handle();

        Bus::assertBatched(function(PendingBatch $batch) {
            return $batch->jobs->count() == 1 &&
                count($batch->jobs[0]) == 2;
        });
    }
}
