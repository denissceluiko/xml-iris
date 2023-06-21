<?php

namespace App\Jobs\Maintenance;

use App\Jobs\Processor\ProcessProducts;
use App\Models\Processor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;

class ProcessForgottenStaleProducts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function middleware()
    {
        return [ (new WithoutOverlapping())->releaseAfter(180)->expireAfter(600) ];
    }

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $results = DB::table('processed_products')
            ->selectRaw('processor_id, count(*) as stale_count')
            ->where('stale_level', '>', '0')
            ->groupBy('processor_id')
            ->get();

        $batch = [];

        foreach($results as $result) {
            if ($result->stale_count < 50) continue;
            $processor = Processor::find($result->processor_id);
            if (!$processor) continue;

            $batch[] = new ProcessProducts($processor);
        }

        if (empty($batch)) return;

        Bus::batch($batch)
            ->name('Maintenance processor run')
            ->onQueue('long-running-queue')
            ->dispatch();
    }
}
