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
    protected bool $full = false;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Compiler $compiler, int $offset, int $count, bool $full = false)
    {
        $this->compiler = $compiler;
        $this->offset = $offset;
        $this->count = $count;
        $this->full = $full;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $builder = $this->compiler
            ->compiledProducts()
            ->select('ean');

        if ($this->full === false) {
            $builder = $builder->stale();
        }

        $compiledProducts = $builder
            ->offset($this->offset)
            ->limit($this->count)
            ->get();

        $batch = [];

        foreach ($compiledProducts as $product)
        {
            $batch[] = new FilterJob($this->compiler, $product->ean);
        }

        Bus::batch($batch)
            ->name('Compile product batch')
            ->onQueue('default')
            ->allowFailures()
            ->dispatch();
    }
}
