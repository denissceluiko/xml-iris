<?php

namespace App\Services\Supplier;

use App\Models\Supplier;
use App\Services\Supplier\Parsers\CSVParser;
use App\Services\Supplier\Parsers\Parser;
use App\Services\Supplier\Parsers\ExcelParser;
use App\Services\Supplier\Parsers\XmlParser;
use Illuminate\Support\Facades\Log;

class ParseService
{
    protected Supplier $supplier;
    protected string $path;

    public function __construct(Supplier $supplier, string $path)
    {
        $this->supplier = $supplier;
        $this->path = $path;
    }

    /**
     * Returns an appropriate parser
     *
     * @return Parser|null
     */
    public function getParser() : ?Parser
    {
        $parser = $this->determineParser();

        if ($parser == 'xml') {
            return $this->xml();
        }

        if ($parser == 'excel') {
            return $this->excel();
        }

        if ($parser == 'csv') {
            return $this->csv();
        }

        Log::channel('import')->error("No parser found.");

        return null;
    }

    protected function determineParser() : ?string
    {
        if ($this->supplier->getSourceType() == 'xml') {
            return 'xml';
        }

        if (in_array( $this->supplier->getSourceType(), ['excel', 'xls', 'xlsx'])) {
            return 'excel';
        }

        if ($this->supplier->getSourceType() == 'csv') {
            return 'csv';
        }

        return null;
    }

    protected function xml() : XmlParser
    {
        return new XmlParser($this->supplier, $this->path);
    }

    protected function excel() : ExcelParser
    {
        return new ExcelParser($this->supplier, $this->path);
    }

    protected function csv() : CSVParser
    {
        return new CSVParser($this->supplier, $this->path);
    }
}
