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
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Sabre\Xml\Writer;
use XMLWriter;

class CompiledProductExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ChonkMeter;

    protected Export $export;
    protected string $path;
    protected string $disk;
    protected XMLWriter $writer;

    public function middleware()
    {
        return [ (new WithoutOverlapping($this->export->id))->expireAfter(600)->dontRelease() ];
    }

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
        $this->writer->startElement($this->export->config['root_tag']);

        for($i=0; $i<$compiledProductCount; $i+=100)
        {
            $this->processChunk($i, $chunkSize);
            $this->logChonk();
        }

        // Save file
        $this->writer->endElement();
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

        foreach ($compiledProducts as $product) {
            $this->writeProduct($product);
        }

        $this->writer->flush();
    }

    protected function writeProduct(CompiledProduct $product)
    {
        $swriter = new Writer();
        $swriter->openMemory();
        $swriter->setIndent(false);

        $filling = $this->export->mappings;
        $swriter->writeElement(
            $this->export->config['product_tag'],
            $this->fillMappings($product, $filling)
        );

        $this->writer->writeRaw($swriter->outputMemory());
    }

    protected function fillMappings(CompiledProduct &$product, array &$mappings) : array
    {
        array_walk($mappings, function (&$value, $key, $product) {
            if (is_array($value)) {
                $value = $this->fillMappings($product, $value);
                return;
            }

            $value = $this->getValue($product, $value);
        }, $product);

        return $mappings;
    }

    protected function getValue(CompiledProduct $product, $key) : ?string
    {
        return $product->data[$key] ?? null;
    }
}
