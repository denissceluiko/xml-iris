<?php

namespace App\Traits;

trait ProductToolkit
{
    /**
     * Determine if an array is a valid product.
     *
     * @param array $product
     * @return boolean
     */
    public function isProduct(array $product) : bool
    {

        if (!isset($product['name']) || strpos($product['name'], '{}') !== 0) return false;
        if (!isset($product['attributes']) || !is_array($product['attributes'])) return false;
        if (!isset($product['value']) || !is_array($product['value'])) return false;

        return true;
    }

    /**
     * Determine if an array is an array of valid products
     *
     * @param array $array
     * @return boolean
     */
    public function isProductArray(array $array) : bool
    {
        foreach ($array as $product) {
            if (!$this->isProduct($product)) return false;
        }

        return true;
    }

    /**
     * Sort the collection keys recursively.
     *
     * @param  int  $options
     * @return static
     */
    public function sortKeysRecursively(array &$array, $options = SORT_REGULAR)
    {
        ksort($array, $options);

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $items[$key] = $this->sortKeysRecursively($value, $options);
            }
        }

        return $array;
    }

}
