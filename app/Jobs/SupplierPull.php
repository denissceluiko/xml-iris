<?php

namespace App\Jobs;

use App\Jobs\Supplier\ParseJob;
use App\Models\Supplier;
use App\Services\Supplier\PullService;
use App\Traits\ChonkMeter;
use Carbon\Carbon;
use Illuminate\Bus\Batchable;
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
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ChonkMeter;

    public Supplier $supplier;


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
        if ( $this->batch()->canceled() ) return;

        if (!$this->canPull()) {
            $this->fail("Cannot pull: config incomplete");
            return;
        }

        // Pull XML
        $path = (new PullService($this->supplier))->pull();

        if ($path == null) {
            $this->fail("Data path failed to load.");
            return;
        }
        
        $this->supplier->update([
            'last_pulled_at' => Carbon::now(),
        ]);

        ParseJob::dispatch($this->supplier, $path);
    }

    public function canPull() : bool
    {
        if (empty($this->supplier->uri)) return false;
        if (!$this->supplier->configKeysSet()) return false;
        if (!is_array($this->supplier->structure) || empty($this->supplier->structure)) return false;

        return true;
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(Throwable $exception)
    {
        $this->log($exception->getMessage());
        $this->delete();
    }

    protected function log(string $message)
    {
        Log::channel('import')->info("[Supplier:\t{$this->supplier->id}] $message");
    }
}
