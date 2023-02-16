<?php

namespace App\Services\Processor;

use FormulaParser\FormulaParser;

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

    public function resolve(string $name, string $rule)
    {
        $rule = $this->insertValues($rule);

        // Future check if is JSON and should be parsed accordingly
        if ($this->isExpression($rule)) {
            $rule = $this->resolveExpression($rule);
        }

        // Let's put into math evaluator in case it has any expressions
        $parser = new FormulaParser($rule, 4);
        $result = $parser->getResult();

        if ($result[0] == 'done') {
            $rule = $result[1];
        }

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

    /**
     * Determines if $rule is a valid expression
     * expression = [left_value, comparator, right_value, expression_if_true, (optional) expression_if_false]
     *
     * @param string|array $rule
     * @return boolean
     */
    public function isExpression(string|array $rule) : bool
    {
        if (is_string($rule)) {
            $data = json_decode($rule);
            // Is it even JSON?
            if (json_last_error() != JSON_ERROR_NONE || !is_array($data)) return false;
        } else {
            $data = $rule;
        }

        // Expression has 5 elements
        if (count($data) != 5) return false;

        // We accept only these comparators
        if (!in_array($data[1], ['<', '=', '>'])) return false;

        // We allow only numbers in comparisons
        if (!is_numeric($data[0]) || !is_numeric($data[2])) return false;

        return true;
    }

    public function resolveExpression(string|array $rule) : string
    {
        if (is_string($rule)) {
            $data = json_decode($rule);
        } else {
            $data = $rule;
        }

        $comparison = $data[0] <=> $data[2];

        if ($comparison == -1 && $data[1] == '<') {
            return $this->isExpression($data[3]) ? $this->resolveExpression($data[3]) : $data[3];
        } else if ($comparison == 0 && $data[1] == '=') {
            return $this->isExpression($data[3]) ? $this->resolveExpression($data[3]) : $data[3];
        } else if ($comparison == 1 && $data[1] == '>') {
            return $this->isExpression($data[3]) ? $this->resolveExpression($data[3]) : $data[3];
        }


        return $this->isExpression($data[4]) ? $this->resolveExpression($data[4]) : $data[4];
    }
}
