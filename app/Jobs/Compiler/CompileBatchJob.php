<?php

namespace App\Jobs\Compiler;

use App\Jobs\Product\FilterJob;
use App\Models\Compiler;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;

class CompileBatchJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Compiler $compiler;
    protected int $offset;
    protected int $count;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Compiler $compiler, int $offset, int $count)
    {
        $this->compiler = $compiler;
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
        $processedProducts = $this->compiler
                                ->compiledProducts()
                                ->select('ean')
                                ->stale()
                                ->offset($this->offset)
                                ->limit($this->count)
                                ->get();

        $batch = [];

        foreach ($processedProducts as $product)
        {
            $batch[] = new FilterJob($this->compiler, $product->ean);
        }

        Bus::batch($batch)
            ->name('Compile product batch')
            ->allowFailures()
            ->dispatch();
    }
}
