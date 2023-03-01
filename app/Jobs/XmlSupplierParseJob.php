<?php

namespace App\Jobs;

use App\Jobs\Product\UpsertJob;
use App\Models\Supplier;
use App\Traits\ChonkMeter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Sabre\Xml\Reader;
use Sabre\Xml\Service;
use XMLReader;

class XmlSupplierParseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ChonkMeter;

    protected Supplier $supplier;
    protected string $path;
    protected XMLReader $reader;

    protected string $namespace;
    protected array $elementMap;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Supplier $supplier, string $path)
    {
        $this->supplier = $supplier;
        $this->path = $path;
        $this->namespace = $supplier->config['namespace'] ?? '';
        $this->onQueue('long-running-queue');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->reader = new XMLReader();

        if ($this->reader->open("file://{$this->path}") === false) {
            $this->fail("Could not open file ({$this->path}) for Supplier: {$this->supplier->id}.");
        }

        while ($this->reader->read() && $this->reader->name != $this->supplier->config['root_tag']);

        $this->elementMap = $this->generateElementMap();

        // Skip all the gunk if there are any
        while ($this->reader->name != $this->supplier->config['product_tag']) {
            $this->reader->read();
        }

        do {
            $parsed = $this->parse($this->reader->readOuterXml());

            $ean = $this->getEAN($parsed);

            if (!empty($ean)) {
                UpsertJob::dispatch($this->supplier, $ean, $parsed);
            }

        } while ($this->reader->next($this->supplier->config['product_tag']));

        $this->reader->close();
    }

    public function parse(string $xml) : array
    {
        $service = new Service();
        $service->elementMap = $this->elementMap;

        $xml = $this->enclose($xml);
        $parsed = $service->parse($xml);
        return $parsed[0];
    }

    protected function generateElementMap() : array
    {
        return  $this->resolveFields($this->supplier->structure);
    }

    /**
     * Resolves fields based on $rules.
     * Removes XML namespaces and creates an assoc array.
     * Does not handle attributes though.
     *
     * @param array $fields
     * @return array
     */
    protected function resolveFields(array $rules) : array
    {
        $map = [];

        foreach ($rules as $name => $field) {
            if (!is_array($field) || !isset($field['type'])) continue;

            if ($field['type'] == 'repeatingElements')
            {
                $map[$this->xmlns($name)] = function (Reader $reader) use ($field) {
                    return $this->repeatingElements($reader, $field['child']);
                };
            } else if ($field['type'] == 'keyValue')
            {
                $map[$this->xmlns($name)] = function (Reader $reader) {
                    return $this->keyValue($reader, $this->namespace);
                };
            }
        }

        return $map;
    }

    /**
     * Adapted from Sabre\XML to process attributes
     *
     * @param Reader $reader
     * @param string $childElementName
     * @return array
     */
    protected function repeatingElements(Reader $reader, string $childElementName): array
    {
        if ('{' !== $childElementName[0]) {
            $childElementName = '{}'.$childElementName;
        }
        $result = [];

        foreach ($reader->parseGetElements() as $element) {
            if ($element['name'] === $childElementName) {
                $result[] = [
                    'name' => $element['name'],
                    'value' => $element['value'],
                    'attributes' => $element['attributes'],
                ];
            }
        }

        return $result;
    }

    /**
     * Adapted from Sabre\XML to process attributes
     *
     * @param Reader $reader
     * @param string|null $namespace
     * @return array
     */
    protected function keyValue(Reader $reader, string $namespace = null): array
    {
        // If there's no children, we don't do anything.
        if ($reader->isEmptyElement) {
            $reader->next();

            return [];
        }

        if (!$reader->read()) {
            $reader->next();

            return [];
        }

        if (Reader::END_ELEMENT === $reader->nodeType) {
            $reader->next();

            return [];
        }

        $values = [];

        do {
            if (Reader::ELEMENT === $reader->nodeType) {
                if (null !== $namespace && $reader->namespaceURI === $namespace) {
                    $values[$reader->localName] = $reader->parseCurrentElement();
                } else {
                    $clark = $reader->getClark();
                    $values[$clark] = $reader->parseCurrentElement();
                }
            } else {
                if (!$reader->read()) {
                    break;
                }
            }
        } while (Reader::END_ELEMENT !== $reader->nodeType);

        $reader->read();

        return $values;
    }

    /**
     * @param string $selector
     * @return string
     */
    protected function xmlns(string $selector = '') : string
    {
        return '{'.$this->namespace.'}'.$selector;
    }

    protected function getEAN(array $product) : ?string
    {
        foreach ($product['value'] as $element) {
            if ($element['name'] == '{}ean')
                return $element['value'];
        }

        return null;
    }

    protected function enclose(string $xml) : string
    {
        $tag = $this->supplier->config['root_tag'];
        return "<{$tag}>".$xml."</{$tag}>";
    }
}
