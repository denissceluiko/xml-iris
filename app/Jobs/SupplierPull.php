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
        // Pull XML
        $supplierRawData = (new PullService($this->supplier))->pull();
        $supplierData = (new ParseService($this->supplier))->parse($supplierRawData);

        $products = $this->getProductsList($supplierData);

        // Divide into batches
        $batch = [];

        foreach ($products as $key => $row)
        {
            $ean = $this->getEAN($row['value']);
            if (empty($ean)) continue;
            $batch[] = new ProductUpsert($this->supplier->id, $ean, $row);
        }

        // Dispatch batches ProductUpdate
        Bus::batch($batch)->then(function (Batch $batch) {

        })->catch(function (Batch $batch, Throwable $e) {

        })->finally(function (Batch $batch) {

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
}
