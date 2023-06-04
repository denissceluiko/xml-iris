<?php

namespace App\Jobs\Processor;

use App\Jobs\Product\Extract;
use App\Jobs\Product\Transform;
use App\Models\ProcessedProduct;
use App\Models\Processor;
use App\Traits\ChonkMeter;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;

class ProcessProductBatch implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ChonkMeter;

    protected Processor $processor;
    protected int $offset;
    protected int $count;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Processor $processor, int $offset, int $count)
    {
        $this->processor = $processor->withoutRelations();

        $this->offset = $offset;
        $this->count = $count;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $products = $this->processor
                    ->processedProducts()
                    ->select(['id', 'stale_level'])
                    ->stale()
                    ->limit($this->count)
                    ->offset($this->offset)
                    ->get();

        $batch = [];

        foreach ($products as $product)
        {
            if ($product->stale_level == 1) {
                $batch[] = $this->transform($product);
            } else {
                $batch[] = $this->full($product);
            }
        }

        Bus::batch($batch)
            ->name('Product processor batch')
            ->allowFailures()
            ->dispatch();
    }

    public function transform(ProcessedProduct $product) : Transform
    {
        return new Transform($product);
    }

    public function full(ProcessedProduct $product) : array
    {
        return [
            new Extract($product),
            new Transform($product),
        ];
    }
}
