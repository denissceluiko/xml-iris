<?php

namespace App\Jobs;

use App\Models\Compiler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;

class UpdateCycle implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->onQueue('long-running-queue');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach(Compiler::all() as $compiler)
        {
            $batch = array_merge(
                $this->getSupplierPullJobs($compiler),
                $this->getProcessProductsJobs($compiler),
            );

            // FYI: non default queue should be explicit when batching jobs.
            Bus::batch($batch)
                ->name("Update Cycle for compiler: {$compiler->name} ({$compiler->id})")
                ->onQueue('long-running-queue')
                ->dispatch();
        }
    }

    public function getSupplierPullJobs(Compiler $compiler) : array
    {
        $jobs = [];

        foreach ($compiler->processors as $processor) {
            $jobs[] = new SupplierPull($processor->supplier);
        }

        return $jobs;
    }

    public function getProcessProductsJobs(Compiler $compiler) : array
    {
        $jobs = [];

        foreach ($compiler->processors as $processor) {
            $jobs[] = new ProcessProducts($processor);
        }

        return $jobs;
    }
}
