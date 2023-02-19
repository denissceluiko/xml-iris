<?php

namespace App\Services\Supplier\Parsers;

use App\Imports\ExcelProductsImport;
use App\Jobs\Supplier\CleanupJob;
use App\Models\Supplier;
use Maatwebsite\Excel\Facades\Excel;

class ExcelParser extends Parser
{
    protected Supplier $supplier;
    protected string $path;

    public function __construct(Supplier $supplier, string $path)
    {
        $this->supplier = $supplier;
        $this->path = $path;
    }

    public function parse() : void
    {
        Excel::import(new ExcelProductsImport($this->supplier), $this->path, null, \Maatwebsite\Excel\Excel::XLSX)
        ->chain([
            new CleanupJob($this->path),
        ]);

    }
}
