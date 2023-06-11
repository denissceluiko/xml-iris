<?php

namespace App\Jobs\Supplier;

use App\Models\Supplier;
use App\Services\Supplier\Parsers\Parser;
use App\Services\Supplier\ParseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ParseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $path;
    protected Supplier $supplier;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Supplier $supplier, string $path, $disk = 'import')
    {
        $this->supplier = $supplier->withoutRelations();
        $this->path = Storage::disk($disk)->path($path);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $service = new ParseService($this->supplier, $this->path);
        $parser = $service->getParser();

        if (! $parser instanceof Parser) {
            $message = "No parser found for Supplier ID: {$this->supplier->id}";

            Log::channel('import')->warning($message);
            $this->fail($message);
            return;
        }

        $pending = $parser->parse();
        $pending->onQueue('long-running-queue')->chain([
            new CleanupJob($this->path),
        ]);
    }
}
