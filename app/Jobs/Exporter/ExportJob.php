<?php

namespace App\Jobs\Exporter;

use App\Models\Export;
use App\Services\Export\ExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Export $export;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Export $export)
    {
        $this->export = $export;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $service = new ExportService($this->export);

        $exporter = $service->getExporter();

        if ($exporter) {
            $exporter->export();
            return;
        }

        $this->fail("No valid exporter for Export:{$this->export->id}");
    }
}
