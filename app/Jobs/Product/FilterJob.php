<?php

namespace App\Jobs\Product;

use App\Models\Compiler;
use App\Services\Compiler\FilterService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FilterJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Compiler $compiler;
    protected string $ean;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Compiler $compiler, string $ean)
    {
        $this->compiler = $compiler->withoutRelations();
        $this->ean = $ean;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->batch()->canceled()) return;

        $products = $this->compiler
            ->processedProducts()
            ->whereIn('processor_id', function ($query) {
                $query->select('id')
                    ->from('processors')
                    ->where('enabled', '1')
                    ->where('compiler_id', $this->compiler->id)
                    ->get();
            })
            ->ean($this->ean)
            ->get();

        $filtered = (new FilterService($this->compiler))->filter($products);

        if (empty($filtered)) {
            Log::info("Filtered empty, Compiler: {$this->compiler->id}; EAN: {$this->ean}");

            // Delete
            $this->compiler
                ->compiledProducts()
                ->ean($this->ean)
                ->where('stale_level', -1)
                ->delete();

            // Mark for deletion on next run
            $this->compiler
                ->compiledProducts()
                ->ean($this->ean)
                ->stale(-1)
                ->update([
                    'processed_product_id' => null,
                    'data->stock' => 0, // Fix this!
                    'stale_level' => -1,
                ]);

        } else {
            $this->compiler->compiledProducts()->ean($this->ean)->update([
                'processed_product_id' => $filtered->id,
                'data' => $filtered->transformed_data,
                'stale_level' => 0,
            ]);
        }
    }
}
