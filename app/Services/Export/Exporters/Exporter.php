<?php

namespace App\Services\Export\Exporters;

use App\Models\Export;

abstract class Exporter
{
    protected Export $export;
    protected string  $filename;

    abstract public function __construct(Export $export, string $filename);

    abstract public function export();

    abstract protected function getFilepath() : string;
}
