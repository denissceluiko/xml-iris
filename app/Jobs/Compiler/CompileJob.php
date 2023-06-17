<?php

namespace App\Jobs\Compiler;

use App\Models\Compiler;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;

class CompileJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Compiler $compiler;

    public function middleware()
    {
        return [ (new WithoutOverlapping($this->compiler->id))->releaseAfter(180)->expireAfter($this->compiler->interval - 1) ];
    }

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Compiler $compiler)
    {
        $this->compiler = $compiler;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ( $this->batch()->canceled() ) return;

        $processedProducts = $this->compiler->processedProducts()->select('ean')->distinct()->get();

        $this->compiler->upsertMissing($processedProducts);

        $CPCount = $this->compiler->compiledProducts()->stale()->count();

        $batch = [];
        $batchSize = 500;

        for ($i=0; $i<$CPCount; $i+=$batchSize)
        {
            $batch[] = new CompileBatchJob($this->compiler, $i, $batchSize);
        }

        if (empty($batch)) return;

        Bus::batch($batch)
            ->name('Compile products master')
            ->onQueue('default')
            ->dispatch();
    }
}
