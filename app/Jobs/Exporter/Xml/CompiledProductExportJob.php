<?php

namespace App\Jobs\Exporter\Xml;

use App\Models\CompiledProduct;
use App\Models\Export;
use App\Traits\ChonkMeter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use XMLWriter;

class CompiledProductExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ChonkMeter;

    protected Export $export;
    protected string $path;
    protected string $disk;
    protected XMLWriter $writer;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Export $export, string $path, string $disk)
    {
        $this->export = $export;
        $this->path = $path;
        $this->disk = $disk;
        $this->onQueue('long-running-queue');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Create file
        $tempFilePath = tempnam(sys_get_temp_dir(), "cpej_{$this->export->id}_");

        $this->writer = new XMLWriter();
        $this->writer->openUri('file://'.$tempFilePath);
        $this->writer->startDocument('1.0', 'utf-8');

        // Create Chain
        $compiledProductCount = $this->export->compiler->compiledProducts()->count();

        $chunkSize = 100;

        for($i=0; $i<$compiledProductCount; $i+=100)
        {
            $this->processChunk($i, $chunkSize);
            $this->logChonk();
        }

        // Save file
        $this->writer->endDocument();
        unset($this->writer);

        copy($tempFilePath, Storage::disk($this->disk)->path($this->path));

        unlink($tempFilePath);
    }

    protected function processChunk($offset, $chunkSize) : void
    {
        $compiledProducts = $this->export
                                ->compiler
                                ->compiledProducts()
                                ->offset($offset)
                                ->limit($chunkSize)
                                ->get();

        $this->writer->startElement($this->export->config['root_tag']);

        foreach ($compiledProducts as $product) {
            $this->writeProduct($product);
        }

        $this->writer->endElement();
        $this->writer->flush();
    }

    protected function writeProduct(CompiledProduct $product)
    {
        $this->writer->startElement($this->export->config['product_tag']);

        foreach($this->export->mappings as $source => $destination) {
            $this->writer->writeElement($destination, $product->data[$source] ?? '');
        }

        $this->writer->endElement();
    }
}
