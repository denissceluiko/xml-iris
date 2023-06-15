<?php

namespace App\Jobs\Supplier;

use App\Jobs\SupplierPull;
use App\Models\Supplier;
use Illuminate\Bus\Batchable;
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
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
        if ( $this->batch()->canceled() ) return;

        $suppliers = Supplier::active()
            ->outdated()
            ->get();

        foreach($suppliers as $supplier) {
            $this->batch()->add(new SupplierPull($supplier));
        }
    }
}
