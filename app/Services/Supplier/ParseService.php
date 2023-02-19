<?php

namespace App\Services\Supplier;

use App\Models\Supplier;
use App\Services\Supplier\Parsers\ExcelParser;
use App\Services\Supplier\Parsers\XmlParser;
use Illuminate\Support\Facades\Log;

class ParseService
{
    protected Supplier $supplier;

    public function __construct(Supplier $supplier)
    {
        $this->supplier = $supplier;
    }

    /**
     * Returns an array of products
     *
     * @param string $path
     * @param boolean $returnAsList
     * @return array|null
     */
    public function parse(string $path) : array|null
    {
        $parser = $this->determineParser();

        if ($parser == 'xml') {
            return $this->xml($path);
        }

        if ($parser == 'excel') {
            return $this->excel($path);
        }

        Log::channel('import')->error("No parser found.");

        return null;
    }

    protected function determineParser() : string|null
    {
        if ($this->supplier->getSourceType() == 'xml') {
            return 'xml';
        }

        if ($this->supplier->getSourceType() == 'excel') {
            return 'excel';
        }

        return null;
    }

    protected function xml(string $path) : array
    {
        return (new XmlParser($this->supplier))->parse($path);
    }

    protected function excel(string $path) : array
    {
        return (new ExcelParser($this->supplier))->parse($path);
    }
}
