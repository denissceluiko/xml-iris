<?php

namespace App\Jobs;

use App\Jobs\Product\Extract;
use App\Jobs\Product\Transform;
use App\Models\ProcessedProduct;
use App\Models\Processor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;

class ProcessProducts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Processor $processor;

    public function middleware() : array
    {
        return [(new WithoutOverlapping($this->processor->id))->releaseAfter(60)->expireAfter(180)];
    }

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Processor $processor)
    {
        $this->processor = $processor->withoutRelations();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->upsertMissing();

        $products = $this->processor->processedProducts()->select(['id', 'stale_level'])->stale()->get();

        $batch = [];

        foreach ($products as $product)
        {
            if ($product->stale_level == 1) {
                $batch[] = $this->transform($product);
            } else {
                $batch[] = $this->full($product);
            }
        }

        Bus::batch($batch)->dispatch();
    }

    public function upsertMissing()
    {
        $products = $this->processor->supplier->products()->select('id', 'ean')->get();

        $processorId = $this->processor->id;

        $upserts = $products->map(function ($product) use ($processorId) {
            return [
                'product_id' => $product->id,
                'ean' => $product->ean,
                'processor_id' => $processorId
            ];
        });

        foreach ($upserts->chunk(500) as $chunk) {
            $this->processor->processedProducts()->upsert($chunk->toArray(), ['product_id']);
        }
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
