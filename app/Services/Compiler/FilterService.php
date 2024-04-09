<?php

namespace App\Services\Compiler;

use App\Models\Compiler;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class FilterService
{
    protected Compiler $compiler;

    public function __construct(Compiler $compiler)
    {
        $this->compiler = $compiler;
    }

    public function filter(Collection $products) : ?Model
    {
        if ($products->isEmpty()) return null;

        if ($products->count() == 1 || empty($this->compiler->rules)) {
            return $products->first();
        }

        $rules = explode("->", $this->compiler->rules);

        foreach ($rules as $rule) {
            $this->apply($rule, $products);
        }

        return $products->first();
    }

    protected function apply(string $rule, Collection &$products)
    {
        $rule = $this->parseRule($rule);

        if ($rule == null) return;

        switch($rule['name']) {
            case 'order':
                $this->order($products, $rule['args']);
                break;
            case 'dropIf':
                $this->dropIf($products, $rule['args']);
                break;
        }
    }

    protected function order(Collection &$products, array $args)
    {
        $products = $products->sort(function ($left, $right) use ($args) {
            if ($args[1] == 'desc')
                return $right->transformed_data[$args[0]] - $left->transformed_data[$args[0]];

            return $left->transformed_data[$args[0]] - $right->transformed_data[$args[0]];
        });

    }

    protected function dropIf(Collection &$products, array $args)
    {
        // Souldn't drop the last product
        if ($products->count() == 1) return;

        // filter() filters the collection, keeping only those items that pass a given truth test.
        // So, drop if expression is true.
        $filtered = $products->filter(function ($product) use ($args) {
            // Ignore if it's the last product or if can't compare
            if (!isset($product->transformed_data[$args[0]]) ) return true;

            return ! $this->compare($product->transformed_data[$args[0]], $args);
        });

        $products = $filtered->isEmpty() ? Collection::wrap($products->first()) : $filtered;
    }

    protected function compare(mixed $value, array $rules) : bool
    {
        if ($rules[1] == '=') {
            return $value == $rules[2];
        } elseif ($rules[1] == '>') {
            return $value > $rules[2];
        } elseif ($rules[1] == '<') {
            return $value < $rules[2];
        }

        // TODO: this should either throw an exception to be logged or be prevented somewhere upstream so illegal comparisons can not be saved.
        return false;
    }

    protected function parseRule(string $rule) : ?array
    {
        if (!str_ends_with($rule, ')')) {
            return null;
        }

        $parts = explode('(', rtrim($rule, ')') );

        if (count($parts) != 2) {
            return null;
        }

        // Check if invalid rule
        if (!in_array($parts[0], ['order', 'dropIf'])) {
            return null;
        }

        $args = explode(',', $parts[1]);

        // Trim quotes
        foreach ($args as &$arg) {
            $arg = trim($arg, '"\' ');
        }

        if ($parts[0] == 'order' && count($args) != 2) {
            return null;
        }

        if ($parts[0] == 'dropIf' && count($args) != 3) {
            return null;
        }

        return [
            'name' => $parts[0],
            'args' => $args,
        ];
    }
}
