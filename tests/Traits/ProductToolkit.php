<?php

namespace Tests\Traits;

use Illuminate\Support\Facades\Storage;

trait ProductToolkit
{
    public function isProductArray(array $array) : bool
    {
        foreach ($array as $product) {
            if (!isset($product['name']) || strpos($product['name'], '{}') !== 0) return false;
            if (!isset($product['attributes']) || !is_array($product['attributes'])) return false;
            if (!isset($product['value'])) return false;
        }

        return true;
    }

}
