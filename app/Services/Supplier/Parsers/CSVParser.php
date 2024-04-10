<?php

namespace App\Services\Supplier\Parsers;

use App\Imports\CSVProductsImport;
use App\Jobs\Supplier\CleanupJob;
use App\Models\Supplier;
use Illuminate\Foundation\Bus\PendingDispatch;
use Maatwebsite\Excel\Facades\Excel;

class CSVParser extends Parser
{
    protected Supplier $supplier;
    protected string $path;

    public function __construct(Supplier $supplier, string $path)
    {
        $this->supplier = $supplier;
        $this->path = $path;
    }

    public function parse() : PendingDispatch
    {
        return (new CSVProductsImport($this->supplier))->queue($this->path, null, \Maatwebsite\Excel\Excel::CSV);
    }
}
