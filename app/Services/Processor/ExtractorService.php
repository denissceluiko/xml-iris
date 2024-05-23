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

    protected function execute($instruction, ?array $tree)
    {
        if (!is_array($tree)) return null;

        $branch = null;

        if (strpos($instruction, 'where') === 0) {
            $branch = $this->where($instruction, $tree);
        } else if ($this->isAttribute($instruction)) {
            $branch = $this->extractAttribute($instruction, $tree);
        } else {
            $branch = $this->firstByName($instruction, $tree);
        }

        return $branch;
    }

    protected function isAttribute($instruction) : bool
    {
        return $instruction[0] == '[' &&
               $instruction[strlen($instruction) - 1] == ']';
    }

    protected function firstByName($name, ?array $tree) : ?array
    {
        if (empty($tree['value'])) return null;

        $results = $this->whereTag($name, $this->stripNode($tree));
        return isset($results[0]) ? $results[0] : null;
    }

    protected function extractAttribute($name, array $element) : array
    {
        // If element is an array of nodes let's take the first
        if (!empty($element) && !$this->isNode($element)) {
            $element = $element[0];
        }

        if (empty($element['attributes'])) return ['value' => null];
        $name = trim($name, "[]");
        return ['value' => $element['attributes'][$name] ?? null];
    }

    protected function where($instruction, array $tree) : ?array
    {
        $instruction = $this->stripFunction($instruction);

        // Normalize so $tree is just an array of nodes.
        $tree = $this->stripNode($tree);

        if (is_null($tree)) {
            return null;
        }

        if ($this->isAttribute($instruction)) {
            [$attribute, $value] = explode('=', trim($instruction, '[]'));
            return $this->whereAttribute($attribute, trim($value, '"\' '), $tree);
        } elseif (true) {
            $tag = trim($instruction, '"');
            return $this->whereTag($tag, $tree);
        }

        return null;
    }

    protected function whereAttribute($attribute, $value, array $tree) : ?array
    {
        if (empty($tree)) return null;

        $filtered = [];

        foreach($tree as $element)
        {
            if (!$this->hasAttribute($attribute, $element)) continue;
            if ($element['attributes'][$attribute] != $value) continue;

            $filtered[] = $element;
        }

        return $filtered;
    }

    protected function whereTag($tag, array $tree) : ?array
    {
        if (empty($tree)) return null;

        $filtered = [];

        foreach ($tree as $element) {
            if(is_string($element)) dd($tree, $element, $tag);
            if ($element['name'] == '{}'.$tag) {
                $filtered[] = $element;
            }
        }

        return $filtered;
    }

    protected function stripFunction(string $instruction)
    {
        return substr($instruction, strpos($instruction, '(') + 1, -1);
    }

    protected function hasAttribute(string $attribute, array $element)
    {
        return array_key_exists($attribute, $element['attributes']);
    }

    protected function isNode(array $tree) : bool
    {
        return isset($tree['name']) &&
               (
                isset($tree['value']) ||
                isset($tree['attributes'])
               );
    }

    protected function stripNode(array $tree) : ?array
    {
        return $this->isNode($tree) ? $tree['value'] : $tree;
    }
}
