<?php

namespace App\Jobs;

use App\Jobs\Supplier\ParseJob;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\Supplier\PullService;
use App\Traits\ChonkMeter;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Throwable;

class SupplierPull implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ChonkMeter;

    public $supplier;


    public function middleware()
    {
        return [ (new WithoutOverlapping($this->supplier->id))->expireAfter(600)->dontRelease() ];
    }

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Supplier $supplier)
    {
        $this->supplier = $supplier->withoutRelations();
        $this->onQueue('long-running-queue');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->log("Starting import");
        $this->logChonk();

        // Pull XML
        $path = (new PullService($this->supplier))->pull();

        if ($path == null) {
            $this->fail("Data path failed to load.");
            $this->log("Data path failed to load.");
        }

        $this->log("Data path loaded.");
        $this->logChonk();

        ParseJob::dispatch($this->supplier, $path);
    }


    protected function log(string $message)
    {
        Log::channel('import')->info("[Supplier:\t{$this->supplier->id}] $message");
    }

}
