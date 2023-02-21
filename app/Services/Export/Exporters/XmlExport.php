<?php

namespace App\Services\Export\Exporters;

use App\Exports\CompiledProductsExport;
use App\Jobs\Exporter\UpdateFilePathEntryJob;
use App\Jobs\Exporter\Xml\CompiledProductExportJob;
use App\Models\Export;
use Maatwebsite\Excel\Facades\Excel;

class XmlExport extends Exporter
{

    public function __construct(Export $export, string $filename)
    {
        $this->export = $export;
        $this->filename = $filename;
    }

    public function export()
    {
        CompiledProductExportJob::dispatch($this->export, $this->getFilepath(), 'export')->chain([
            new UpdateFilePathEntryJob($this->export, $this->getFilepath()),
        ]);
    }

    protected function getFilepath(): string
    {
        return $this->filename.'.xml';
    }
}
