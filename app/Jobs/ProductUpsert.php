<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProductUpsert implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Queueable;

    public $supplier_id;
    public $ean;
    public $values;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(int $supplier_id, string $ean, array $values)
    {
        $this->supplier_id = $supplier_id;
        $this->ean = $ean;
        $this->values = $values;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ( $this->batch()->canceled()) return;

        $product = Product::firstOrNew([
            'supplier_id' => $this->supplier_id,
            'ean' => $this->ean
        ], ['values' => $this->values]);

        $product->supplier_id = $this->supplier_id;

        $product->save();
    }
}
