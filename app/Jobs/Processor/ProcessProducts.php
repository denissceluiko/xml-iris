<?php

namespace App\Jobs\Processor;

use App\Jobs\Processor\ProcessProductBatch;
use App\Jobs\Product\Extract;
use App\Jobs\Product\Transform;
use App\Models\ProcessedProduct;
use App\Models\Processor;
use App\Traits\ChonkMeter;
use Illuminate\Bus\Batchable;
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
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ChonkMeter;

    protected Processor $processor;

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
        if ( $this->batch()->canceled() ) return;

        $this->upsertMissing();

        $this->logChonk();
        $count = $this->processor->processedProducts()->stale()->count();

        $batch = [];

        $batchSize = 500;

        for ($i=0; $i<$count; $i+=$batchSize)
        {
            $batch[] = new ProcessProductBatch($this->processor, $i, $batchSize);
        }

        Bus::batch($batch)
            ->name('Product processor master')
            ->onQueue('long-running-queue')
            ->dispatch();
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
}
