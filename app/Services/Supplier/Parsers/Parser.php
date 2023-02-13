<?php

namespace App\Services\Supplier\Parsers;

abstract class Parser
{
    protected array $rules;

    abstract protected function __construct(array $rules);

    abstract protected function parse(string $data) : array;
}
