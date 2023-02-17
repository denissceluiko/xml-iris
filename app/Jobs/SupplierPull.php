<?php

namespace App\Jobs;

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
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SupplierPull implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->log('Starting import for supplier '. $this->supplier->id);

        // Pull XML
        $path = (new PullService($this->supplier))->pull();
        $supplierRawData = Storage::disk('import')->get($path);
        $this->log("Supplier {$this->supplier->id} data loaded.");
        $supplierData = (new ParseService($this->supplier))->parse($supplierRawData);
        $this->log("Supplier {$this->supplier->id} data parsed");

        $products = $this->getProductsList($supplierData);

        // Divide into batches
        $batch = [];

        foreach ($products as $key => $row)
        {
            $ean = $this->getEAN($row['value']);
            if (empty($ean)) continue;
            $batch[] = new ProductUpsert($this->supplier->id, $ean, $row);
        }

        $this->log("Supplier {$this->supplier->id} batch upserting started");
        // Dispatch batches ProductUpdate
        Bus::batch($batch)->then(function (Batch $batch) {
            Log::channel('import')->info("Supplier upserting successful");
            // $this->log();
        })->catch(function (Batch $batch, Throwable $e) {
            Log::channel('import')->info("Supplier data exception: {$e->getMessage()}");
            // $this->log();
        })->finally(function (Batch $batch) use($path) {
            Storage::disk('import')->delete($path);
            Log::channel('import')->info("Supplier loading finalized.");
            // $this->log();
        })->dispatch();
    }

    /**
     * Simplified extractor in case root tag is not the product container
     *
     * @param array $xmlArray
     * @return array
     */
    protected function getProductsList(array $xmlArray) : array
    {
        return  isset($xmlArray[$this->config('root_tag')])
                    ? $xmlArray[$this->config('root_tag')]['value']
                    : $xmlArray;
    }

    protected function getEAN(array $productRow) : string
    {
        foreach ($productRow as $field) {
            if ($field['name'] == "{}ean") {
                return $field['value'];
            }
        }
        return '';
    }

    protected function config(string $key) : string
    {
        return $this->supplier->config[$key] ?? '';
    }

    protected function log(string $message)
    {
        Log::channel('import')->info($message);
    }
}
