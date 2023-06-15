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

        $batch = [
            $this->getSupplierPullJobs($suppliers),
            $this->getProcessProductsJobs($suppliers)
        ];

        Bus::batch($batch)
            ->name("Update Cycle")
            ->allowFailures()
            ->onQueue('long-running-queue')
            ->dispatch();
    }

    public function getSupplierPullJobs(Collection $suppliers) : array
    {
        $jobs = [];

        foreach($suppliers as $supplier) {
            $jobs[] = new SupplierPull($supplier);
        }

        return $jobs;
    }

    public function getProcessProductsJobs(Collection $suppliers) : array
    {
        $jobs = [];

        foreach ($suppliers as $supplier) {
            $jobs = array_merge($jobs, $this->getProcessProductsJobsForSupplier($supplier));
        }

        return $jobs;
    }

    public function getProcessProductsJobsForSupplier(Supplier $supplier) : array
    {
        $jobs = [];

        foreach ($supplier->processors as $processor) {
            $jobs[] = new ProcessProducts($processor);
        }

        return $jobs;
    }
}
