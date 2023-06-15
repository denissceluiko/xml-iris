<?php

namespace App\Jobs;

use App\Jobs\Processor\ProcessProducts;
use App\Models\Supplier;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
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
        $suppliers = Supplier::active()
            ->outdated()
            ->with(['processors'])
            ->get();

        $batch = [];

        foreach ($suppliers as $supplier) {
            $batch[] = $this->getJobsForSupplier($supplier);
        }

        if (empty($batch)) return;
        
        Bus::batch($batch)
            ->name("Update Cycle")
            ->allowFailures()
            ->onQueue('long-running-queue')
            ->dispatch();
    }

    public function getJobsForSupplier(Supplier $supplier) : array
    {
        $jobs = [];
        $jobs[] = new SupplierPull($supplier);

        foreach ($supplier->processors as $processor) {
            $jobs[] = new ProcessProducts($processor);
        }

        return $jobs;
    }
}
