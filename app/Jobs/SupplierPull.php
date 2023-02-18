<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\Supplier;
use App\Services\Supplier\ParseService;
use App\Services\Supplier\PullService;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SupplierPull implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $supplier;


    // public function middleware()
    // {
    //     return [ (new WithoutOverlapping($this->supplier->id))->expireAfter(600)->dontRelease() ];
    // }

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
        $this->log("Memory: ".$this->processPeakMemUsage());

        // Pull XML
        $path = (new PullService($this->supplier))->pull();

        if ($path == null) {
            $this->fail("Data path failed to load.");
            $this->log("Data path failed to load.");
        }

        $this->log("Data path loaded.");
        $this->log("Memory: ".$this->processPeakMemUsage());

        $products = (new ParseService($this->supplier))->parse($path);
        $this->log("Data parsed");
        $this->log("Products found: ".count($products));
        $this->log("Memory: ".$this->processPeakMemUsage());

        // Divide into batches
        $batch = [];
        $noEANCount = 0;

        foreach ($products as $row)
        {
            $ean = $this->getEAN($row['value']);
            if (empty($ean)) {
                $noEANCount++;
                continue;
            }

            $batch[] = [
                'ean' => $ean,
                'supplier_id' => $this->supplier->id,
                'values' => json_encode($row),
            ];

            if (count($batch) > 100) {
                $this->upsert($batch);
                $batch = [];
            }
        }

        $this->upsert($batch);

        $this->log("Products processed");
        $this->log("Memory: ".$this->processPeakMemUsage());

        if ($noEANCount > 0) {
            $this->log("Products dismissed due to no EAN code: {$noEANCount}");
        }

        // Clean up the imported file
        Storage::disk('import')->delete($path);
        $this->log("Import finished.");
    }

    protected function getEAN(array $productRow) : string
    {
        foreach ($productRow as $field) {
            if ($field['name'] == "{}ean") {
                return $field['value'] ?? '';
            }
        }
        return '';
    }

    protected function upsert(array $chunk) : void
    {
        Product::upsert($chunk, ['supplier_id', 'ean'], ['values']);
    }

    protected function config(string $key) : string
    {
        return $this->supplier->config[$key] ?? '';
    }

    protected function log(string $message)
    {
        Log::channel('import')->info("[Supplier:\t{$this->supplier->id}] $message");
    }

    //memcheck.php
    /**
     * Gets peak memory usage of a process in KiB from /proc.../status.
     *
     * @return int|bool VmPeak, value in KiB. False if data could not be found.
     */
    protected function processPeakMemUsage()
    {
        $status = file_get_contents('/proc/' . getmypid() . '/status');
        $matches = array();
        preg_match_all('/^(VmPeak):\s*([0-9]+).*$/im', $status, $matches);
        return !isset($matches[2][0]) ? false : intval($matches[2][0]);
    }
}
