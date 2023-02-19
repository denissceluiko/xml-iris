<?php

namespace App\Services\Supplier\Parsers;

use App\Models\Supplier;

abstract class Parser
{
    protected Supplier $supplier;

    abstract protected function __construct(Supplier $supplier, string $path);

    abstract protected function parse() : void;
}
