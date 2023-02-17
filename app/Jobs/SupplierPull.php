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

        // Pull XML
        $path = (new PullService($this->supplier))->pull();
        $this->log("Data path loaded.");

        $supplierData = (new ParseService($this->supplier))->parse($path);
        $this->log("Data parsed");

        $products = $this->getProductsList($supplierData);
        $this->log("Products found: ".count($products));

        // Divide into batches
        $batch = [];

        foreach ($products as $key => $row)
        {
            $ean = $this->getEAN($row['value']);
            if (empty($ean)) continue;

            $batch[] = [
                'ean' => $ean,
                'supplier_id' => $this->supplier->id,
                'values' => json_encode($row['value']),
            ];
        }

        $this->log("Products encoded");

        // Insert
        foreach(array_chunk($batch, 100, true) as $chunk)
        {
            $this->upsert($chunk);
        }

        // Clean up the imported file
        Storage::disk('import')->delete($path);
        $this->log("Import finished.");
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
}
