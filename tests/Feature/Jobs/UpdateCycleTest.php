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
                   $batch->jobs[0][1] instanceof SupplierPull &&
                   $batch->jobs[1][0] instanceof ProcessProducts &&
                   $batch->jobs[1][1] instanceof ProcessProducts;
        });
    }
}
