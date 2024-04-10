<?php

namespace App\Services\Export\Exporters;

use App\Exports\CompiledProductsExport;
use App\Jobs\Exporter\UpdateFilePathEntryJob;
use App\Models\Export;
use Maatwebsite\Excel\Facades\Excel;

class ExcelExport extends Exporter
{
    public function __construct(Export $export, string $filename)
    {
        $this->export = $export;
        $this->filename = $filename;
    }

    public function export()
    {
        Excel::store(new CompiledProductsExport($this->export), $this->getFilepath(), 'export', \Maatwebsite\Excel\Excel::XLSX)->chain([
            new UpdateFilePathEntryJob($this->export, $this->getFilepath()),
        ]);
    }

    protected function getFilepath(): string
    {
        return $this->filename.'.xlsx';
    }
}
