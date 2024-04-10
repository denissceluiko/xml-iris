<?php

namespace App\Jobs;

use App\Jobs\Compiler\CompileJob;
use App\Jobs\Exporter\ExportJob;
use App\Models\Compiler;
use App\Models\Supplier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;

class ExportCycle implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->onQueue('long-running-queue');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $compilers = Compiler::active()
            ->outdated()
            ->orderBy('last_compiled_at', 'asc')
            ->get();

        $batch = [];

        foreach($compilers as $compiler)
        {
            $batch[] = array_merge(
                [new CompileJob($compiler)],
                $this->getExportJobs($compiler),
            );
        }

        if (empty($batch)) return;

        Bus::batch($batch)
            ->name('Export Cycle')
            ->onQueue('long-running-queue')
            ->dispatch();
    }

    public function getExportJobs(Compiler $compiler) : array
    {
        $jobs = [];

        foreach ($compiler->exports as $export) {
            $jobs[] = new ExportJob($export);
        }

        return $jobs;
    }
}
