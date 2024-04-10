<?php

namespace App\Jobs\Product;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CacheInvalidateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Product $product;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $updated = $this->product->processedProducts()->update(['stale_level' => 2]);

        if ($updated > 0) {
            $this->invalidateCompiledProducts();
        }
    }

    public function invalidateCompiledProducts() : void
    {
        $processedProducts = $this->product->processedProducts()->get();

        if ($processedProducts->isEmpty()) {
            return;
        }

        foreach($processedProducts as $pproduct) {
            $pproduct->compiledProducts()->update(['stale_level' => 1]);
        }
    }
}
