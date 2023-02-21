<?php

namespace App\Services\Export;

use App\Models\Export;
use App\Services\Export\Exporters\ExcelExport;
use App\Services\Export\Exporters\Exporter;
use App\Services\Export\Exporters\XmlExport;

class ExportService
{
    protected Export $export;
    protected string $filename;

    public function __construct(Export $export, ?string $filename = null)
    {
        $this->export = $export;
        $this->filename = $filename ?? $this->createFilename();
    }

    public function getExporter() : ?Exporter
    {
        if (strtolower($this->export->type) == 'excel') {
            return new ExcelExport($this->export, $this->filename);
        }

        if (strtolower($this->export->type) == 'xml') {
            return new XmlExport($this->export, $this->filename);
        }

        return null;
    }

    protected function createFilename() : string
    {
        return 'export_'.sha1(microtime().$this->export->id);
    }
}
