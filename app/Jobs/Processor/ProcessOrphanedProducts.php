<?php

namespace App\Jobs\Processor;

use App\Jobs\Product\Transform;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;

class ProcessOrphanedProducts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Supplier $supplier;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Supplier $supplier)
    {
        $this->supplier = $supplier->withoutRelations();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->supplier->last_pulled_at === null) {
            $this->fail('Supplier\'s last_pulled_at cannot be null.');
            return;
        }

        // Gather
        $orphans = $this->supplier
            ->products()
            ->orphaned($this->supplier->last_pulled_at)
            ->select(['id', 'ean'])
            ->with(['processedProducts'])
            ->get();

        // Create transformer batches
        $batch = [];
        foreach ($orphans as $orphan)
        {
            array_push($batch, $this->createBatchForOrphan($orphan));
        }

        // Dispatch the batch
        Bus::batch($batch)
            ->name('Orphaned product processing')
            ->allowFailures()
            ->dispatch();
    }

    public function createBatchForOrphan(Product $product) : array
    {
        if ($product->processedProducts->isEmpty()) return [];

        $subbatch = [];
        foreach ($product->processedProducts as $pproduct)
        {
            $subbatch[] = new Transform($pproduct);
        }

        return $subbatch;
    }
}
