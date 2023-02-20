<?php

namespace App\Jobs\Compiler;

use App\Jobs\Product\FilterJob;
use App\Models\Compiler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;

class CompileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Compiler $compiler;
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
        $processedProducts = $this->compiler->processedProducts()->select('ean')->distinct()->get();

        $this->upsertMissing($processedProducts);

        $batch = [];

        foreach ($processedProducts as $product)
        {
            $batch[] = new FilterJob($this->compiler, $product->ean);
        }

        Bus::batch($batch)->name('Compile products')->dispatch();
    }

    public function upsertMissing(Collection $EANs)
    {
        $compilerId = $this->compiler->id;

        $upserts = $EANs->map(function ($ean) use ($compilerId) {
            return [
                'compiler_id' => $compilerId,
                'ean' => $ean->ean,
            ];
        });

        foreach ($upserts->chunk(1000) as $chunk) {
            $this->compiler->compiledProducts()->upsert($chunk->toArray(), ['compiler_id', 'ean']);
        }
    }
}
