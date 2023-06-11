<?php

namespace App\Services\Supplier\Parsers;

use App\Jobs\Supplier\CleanupJob;
use App\Jobs\XmlSupplierParseJob;
use App\Models\Supplier;
use Illuminate\Foundation\Bus\PendingDispatch;
use Sabre\Xml\Reader;
use Sabre\Xml\Service;

class XmlParser extends Parser
{
    protected Supplier $supplier;
    protected string $namespace;
    protected string $path;

    public function __construct(Supplier $supplier, string $path)
    {
        $this->supplier = $supplier;
        $this->namespace = $supplier->config['xmlns'] ?? '';
        $this->path = $path;
    }

    public function parse() : PendingDispatch
    {
        return XmlSupplierParseJob::dispatch($this->supplier, $this->path);
    }
}
