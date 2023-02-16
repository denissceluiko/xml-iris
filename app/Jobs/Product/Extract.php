<?php

namespace App\Jobs\Product;

use App\Models\ProcessedProduct;
use App\Services\Processor\ExtractorService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class Extract implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public ProcessedProduct $product;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(ProcessedProduct $product)
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
        if ( $this->batch()->canceled() ) return;

        $extractor = new ExtractorService($this->product->processor->mappings);
        $this->product->extracted_data = $extractor->extract($this->product->product->values);
        $this->product->setStale("transformed")->save();
    }
}
