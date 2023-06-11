<?php

namespace App\Services\Supplier\Parsers;

use App\Models\Supplier;
use Illuminate\Foundation\Bus\PendingDispatch;

abstract class Parser
{
    protected Supplier $supplier;

    abstract public function __construct(Supplier $supplier, string $path);

    abstract public function parse() : PendingDispatch;
}
