<?php

namespace App\Jobs\Product;

use App\Models\ProcessedProduct;
use App\Services\Processor\TransformerService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class Transform implements ShouldQueue
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

        $transformer = new TransformerService($this->product->processor->transformations, $this->product->processor->compiler->fields, $this->product->extracted_data);
        $this->product->transformed_data = $transformer->transform();
        $this->product->setStale("fresh")->save();
    }
}
