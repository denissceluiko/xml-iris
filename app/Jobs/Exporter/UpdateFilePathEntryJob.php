<?php

namespace App\Jobs\Exporter;

use App\Models\Export;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class UpdateFilePathEntryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Export $export;
    protected string $path;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Export $export, string $path)
    {
        $this->export = $export;
        $this->path = $path;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (!empty($this->export->path)) {
            Storage::disk('export')->delete($this->export->path);
        }

        $this->export->update([
            'path' => $this->path
        ]);
    }
}
