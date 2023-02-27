<?php

namespace App\Jobs;

use App\Jobs\Product\Extract;
use App\Jobs\Product\Transform;
use App\Models\ProcessedProduct;
use App\Models\Processor;
use App\Traits\ChonkMeter;
use Illuminate\Bus\PendingBatch;
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
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ChonkMeter;

    protected Processor $processor;
    protected PendingBatch $batch;

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
        $this->onQueue('long-running-queue');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->upsertMissing();

        $this->logChonk();
        $count = $this->processor->processedProducts()->stale()->count();

        $this->batch = Bus::batch([])->name('Products processing');

        for($i=0; $i<$count; $i+=200)
        {
            $this->processChunk($i, 200);
        }

        $this->batch->dispatch();
    }

    public function upsertMissing()
    {
        $products = $this->processor->supplier->products()->select('id', 'ean')->get();

        $processorId = $this->processor->id;

        $upserts = $products->map(function ($product) use ($processorId) {
            return [
                'product_id' => $product->id,
                'ean' => $product->ean,
                'processor_id' => $processorId,
            ];
        });

        foreach ($upserts->chunk(500) as $chunk) {
            $this->processor->processedProducts()->upsert($chunk->toArray(), ['product_id']);
        }
    }

    protected function processChunk(int $offset, int $count) : void
    {
        $products = $this->processor
                    ->processedProducts()
                    ->select(['id', 'stale_level'])
                    ->stale()
                    ->limit($count)
                    ->offset($offset)
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

        $this->batch->add($batch);
        $this->logChonk();
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
