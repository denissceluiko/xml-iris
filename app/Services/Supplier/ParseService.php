<?php

namespace App\Services\Supplier;

use App\Models\Supplier;
use App\Services\Supplier\Parsers\Xml;

class ParseService
{
    protected Supplier $supplier;

    public function __construct(Supplier $supplier)
    {
        $this->supplier = $supplier;
    }

    public function parse(string $path) : array
    {
        $parser = $this->determineParser();

        if ($parser == 'xml') {
            return $this->xml($path);
        }

        return [];
    }

    protected function determineParser() : string|null
    {
        if ($this->supplier->getSourceType() == 'xml') {
            return 'xml';
        }

        return null;
    }

    protected function xml(string $path)
    {
        return (new Xml($this->supplier))->parse($path);
    }
}
