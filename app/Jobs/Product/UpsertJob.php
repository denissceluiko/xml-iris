<?php

namespace App\Jobs\Product;

use App\Models\Supplier;
use App\Traits\ProductToolkit;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpsertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ProductToolkit;

    public Supplier $supplier;
    public string $ean;
    public array $values;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Supplier $supplier, string $ean, array $values)
    {
        $this->supplier = $supplier;
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
        if (!$this->isProduct($this->values)) {
            $this->fail("Invalid product, skipping. Supplier: {$this->supplier->id}; EAN: {$this->ean}");
            return;
        }

        $product = $this->supplier->products()->where('ean', $this->ean)->first();

        if ($product === null) {
            $this->supplier->products()->create([
                'ean' => $this->ean,
                'values' => $this->values,
            ]);

            return;
        }

        if ($product->values == $this->values) {
            $product->update([
                'last_pulled_at' => Carbon::now(),
            ]);
            return;
        }

        $product->update([
            'values' => $this->values,
            'last_pulled_at' => Carbon::now(),
        ]);

        CacheInvalidateJob::dispatch($product);
    }
}
