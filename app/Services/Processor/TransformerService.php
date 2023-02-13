<?php

namespace App\Services\Processor;

class TransformerService
{

    protected array $transformations;   // From Processor
    protected array $types;             // From Compiler
    protected array $data;              // From Extractor

    public function __construct(array $transformations, array $types, array $data)
    {
        $this->transformations = $transformations;
        $this->types = $types;
        $this->data = $data;
    }

    public function transform()
    {
        $values = [];

        foreach ($this->transformations as $name => $rule) {
            $values[$name] = $this->resolve($name, $rule);
        }

        return $values;
    }

    public function resolve(string $name, $rule)
    {
        $rule = $this->insertValues($rule);

        // Future check if is JSON and should be parsed accordingly
        // If not and it should be numeric in the end should be put into math evaluator

        return $this->setType($name, $rule);
    }

    /**
     * Replaces transformations' names within a rule with theri values
     * E.g.
     * $transformations = ["ean" => "ean", "sku" => "sku", "extra" => "ean sku"]
     * $data = ["ean" => "101010101", "sku" => "20202"]
     * result would be ["ean" => "101010101", "sku" => "20202", "extra" => "101010101 20202"]
     *
     * @param [type] $rule
     * @return void
     */
    public function insertValues($rule)
    {
        foreach ($this->transformations as $name => $value) {
            if (empty($this->data[$name])) continue;
            $rule = str_replace($name, $this->data[$name], $rule);
        }

        return $rule;
    }

    public function setType(string $name, $rule)
    {
        switch ($this->types[$name]) {
            case 'int':
                $rule = intval($rule);
                break;
            case 'float':
                $rule = floatval($rule);
                break;
        }

        return $rule;
    }
}
