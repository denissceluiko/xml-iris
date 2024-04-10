<?php

namespace App\Jobs\Maintenance;

use App\Models\Compiler;
use App\Models\Supplier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PurgeAbandonedProducts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach (Supplier::all() as $supplier) {
            $this->cleanSupplier($supplier);
        }

        foreach (Compiler::all() as $compiler) {
            $this->cleanCompiler($compiler);
        }
    }

    public function cleanSupplier(Supplier $supplier)
    {
        $supplier->products()->abandoned()->delete();
    }

    public function cleanCompiler(Compiler $compiler)
    {
        $compiler->compiledProducts()->orphaned()->delete();
    }
}
