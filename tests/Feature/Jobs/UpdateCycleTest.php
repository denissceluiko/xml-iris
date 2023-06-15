<?php

namespace Tests\Feature\Jobs;

use App\Jobs\UpdateCycle;
use App\Models\Compiler;
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
            ->name('Test compiler')
            ->hasProcessors(2)
            ->create();

        $job = new UpdateCycle();
        $job->handle();

        Bus::assertBatchCount(1);
        Bus::assertBatched(function (PendingBatch $batch) use ($compiler) {
            return $batch->name == "Update Cycle for compiler: {$compiler->name} ({$compiler->id})" &&
                   $batch->queue() == 'long-running-queue' &&
                   $batch->jobs->count() == 3; // 2 processors + PullDispatch
        });
    }

    /**
     * @test
     * @return void
     */
    public function will_prepare_and_dispatch_update_cycle_for_multiple_compilers()
    {
        Bus::fake();

        $compiler = Compiler::factory()
            ->state(new Sequence([
                'name' => 'Test compiler 1',
                'name' => 'Test compiler 2',
            ]))
            ->hasProcessors(2)
            ->count(2)
            ->create();

        $job = new UpdateCycle();
        $job->handle();

        Bus::assertBatchCount(2);
    }
}
