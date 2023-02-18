<?php

namespace App\Services\Supplier\Parsers;

use App\Models\Supplier;

abstract class Parser
{
    protected Supplier $supplier;

    abstract protected function __construct(Supplier $supplier);

    abstract protected function parse(string $path) : array;
}
