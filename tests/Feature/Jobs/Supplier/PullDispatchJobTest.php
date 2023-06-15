<?php

namespace Tests\Feature\Jobs\Supplier;

use App\Jobs\Supplier\PullDispatchJob;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Bus\PendingBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PullDispatchJobTest extends TestCase
{
    use RefreshDatabase;
    /**
     * @test
     * @return void
     */
    public function can_dispatch_supplier_pull_jobs()
    {
        Bus::fake();

        $this->travel(now()->subHours(2));
        Supplier::factory() // Should update
            ->pullInterval(3600)
            ->create();

        Supplier::factory() // Should not update
            ->pullInterval(86400)
            ->pulledAt(now()->subHours(1)->subMinutes(45))
            ->create();

        $this->travel(now());

        $job = new PullDispatchJob();
        $job->handle();

        Bus::assertBatchCount(1);
        Bus::assertBatched(function(PendingBatch $batch) {
            return $batch->name == 'Scheduled supplier pull' &&
                   $batch->jobs->count() == 1;
        });
    }

    /**
     * @test
     * @return void
     */
    public function will_not_pull_if_interval_is_zero()
    {
        Bus::fake();

        $this->travel(now()->subHours(2));
        Supplier::factory()
            ->pullInterval(0)
            ->pulledAt(now()->subHours(1))
            ->create();

        $this->travel(now());

        $job = new PullDispatchJob();
        $job->handle();

        Bus::assertNothingBatched();
    }
}
