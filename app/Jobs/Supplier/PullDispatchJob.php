<?php

namespace App\Jobs\Supplier;

use App\Jobs\SupplierPull;
use App\Models\Supplier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;

class PullDispatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function middleware()
    {
        return [ (new WithoutOverlapping())->dontRelease() ];
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
        $suppliers = Supplier::active()
            ->outdated()
            ->get();
            
        $batch = [];

        foreach($suppliers as $supplier) {
            $batch[] = new SupplierPull($supplier);
        }

        if (empty($batch)) return;

        Bus::batch($batch)
            ->name('Scheduled supplier pull')
            ->allowFailures()
            ->dispatch();
    }
}
