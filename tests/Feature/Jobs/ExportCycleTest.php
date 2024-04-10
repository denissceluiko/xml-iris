<?php

namespace Tests\Feature\Jobs;

use App\Jobs\Compiler\CompileJob;
use App\Jobs\ExportCycle;
use App\Jobs\Exporter\ExportJob;
use App\Models\Compiler;
use Illuminate\Bus\PendingBatch;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class ExportCycleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @return void
     */
    public function will_prepare_and_dispatch_export_cycle()
    {
        Bus::fake();

        $compiler = Compiler::factory()
            ->name('Test compiler')
            ->hasExports(1)
            ->interval(3600)
            ->compiledAt(now()->subMinutes(61))
            ->create();

        $job = new ExportCycle();
        $job->handle();

        Bus::assertBatchCount(1);
        Bus::assertBatched(function (PendingBatch $batch) use ($compiler) {
            return $batch->name == 'Export Cycle' &&
                   $batch->queue() == 'long-running-queue' &&
                   $batch->jobs->count() == 1 &&
                   $batch->jobs[0][0] instanceof CompileJob &&
                   $batch->jobs[0][1] instanceof ExportJob;
        });
    }

    /**
     * @test
     * @return void
     */
    public function can_handle_first_time_compilation()
    {
        Bus::fake();

        $compiler = Compiler::factory()
            ->name('Test compiler')
            ->hasExports(1)
            ->interval(3600)
            ->state([
                'last_compiled_at' => null,
            ])
            ->create();

        $job = new ExportCycle();
        $job->handle();

        Bus::assertBatchCount(1);
        Bus::assertBatched(function (PendingBatch $batch) use ($compiler) {
            return $batch->name == 'Export Cycle' &&
                   $batch->queue() == 'long-running-queue' &&
                   $batch->jobs->count() == 1 &&
                   $batch->jobs[0][0] instanceof CompileJob &&
                   $batch->jobs[0][1] instanceof ExportJob;
        });
    }

    /**
     * @test
     * @return void
     */
    public function will_not_dispatch_export_cycle_before_its_time()
    {
        Bus::fake();

        $compiler = Compiler::factory()
            ->name('Test compiler')
            ->hasExports(1)
            ->interval(3600)
            ->compiledAt(now()->subMinutes(45))
            ->create();

        $job = new ExportCycle();
        $job->handle();

        Bus::assertNothingBatched();
    }
}
