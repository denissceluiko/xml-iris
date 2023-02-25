<?php

namespace App\Services\Processor;

class ExtractorService
{
    protected array $rules;
    protected array $cache = [];

    public function __construct(array $rules)
    {
        $this->rules = $rules;
    }

    public function extract(array $source = [])
    {
        $values = [];

        foreach ($this->rules as $name => $rule)
        {
            $values[$name] = $this->cache[$rule] ?? $this->resolve($rule, $source);
        }

        return $values;
    }

    protected function resolve($rule, array $tree) : mixed
    {
        $path = explode('->', $rule);

        if (empty($path[0])) return null;

        foreach($path as $instruction) {
            $tree = $this->execute($instruction, $tree);
        }

        $this->cache[$rule] = $tree['value'] ?? null;

        return $tree['value'] ?? null;
    }

    protected function execute($instruction, array $tree)
    {
        $field = null;

        if (strpos($instruction, 'where') === 0) {
            $field = $this->where($instruction, $tree)['value'];
        } else if ($this->isAttribute($instruction)) {
            $field = $this->extractAttribute($instruction, $tree);
        } else {
            $field = $this->findByName($instruction, $tree['value']);
        }

        return $field;
    }

    protected function isAttribute($instruction) : bool
    {
        return $instruction[0] == '[' &&
               $instruction[strlen($instruction) - 1] == ']';
    }

    protected function findByName($name, array $tree) : array
    {
        foreach ($tree as $element) {
            if ($element['name'] == '{}'.$name) {
                return $element;
            }
        }

        return ['value' => null];
    }

    protected function extractAttribute($name, array $element) : array
    {
        if (empty($element['attributes'])) return ['value' => null];
        $name = trim($name, "[]");
        return ['value' => $element['attributes'][$name] ?? null];
    }

    protected function where($instruction, array $tree) : array
    {
        $instruction = $this->stripFunction($instruction);

        if ($this->isAttribute($instruction)) {
            [$attribute, $value] = explode('=', trim($instruction, '[]'));
            return $this->whereAttribute($attribute, trim($value, '"\' '), $tree);
        }

        return ['value' => null];
    }

    protected function whereAttribute($attribute, $value, array $tree) : array
    {
        foreach($tree['value'] as $element)
        {
            if (!$this->hasAttribute($attribute, $element)) continue;
            if ($element['attributes'][$attribute] != $value) continue;

            return ['value' => $element];
        }

        return ['value' => null];
    }

    protected function stripFunction(string $instruction)
    {
        return substr($instruction, strpos($instruction, '(') + 1, -1);
    }

    protected function hasAttribute(string $attribute, array $element)
    {
        return array_key_exists($attribute, $element['attributes']);
    }
}
