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
        $this->product = $product->withoutRelations();
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

        $extractedData = $extractor->extract($this->product->product->values);

        if (empty($extractedData)) {
            $this->fail("Data extraction failed");
            return;
        }
        
        $this->product
            ->setStale("transformed")
            ->setMeta([
                '__last_pulled_at' => $this->product->product->last_pulled_at,
            ])->update([
                'extracted_data' => $extractedData,
            ]);
    }
}
