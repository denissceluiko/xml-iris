<?php

namespace Tests\Unit\Traits;

use App\Traits\ProductToolkit;
use PHPUnit\Framework\TestCase;

class ProductToolkitTest extends TestCase
{
    /**
     * @test
     * @return void
     */
    public function can_validate_proper_product()
    {
        $trait = new class {
            use ProductToolkit;
        };

        $product = [
            'name' => '{}name',
            'value' => [],
            'attributes' => [],
        ];

        $this->assertTrue($trait->isProduct($product));
    }

    /**
     * @test
     * @return void
     */
    public function can_fail_name_structure()
    {
        $trait = new class {
            use ProductToolkit;
        };

        $product = [
            'name' => 'name',
            'value' => [],
            'attributes' => [],
        ];

        $this->assertFalse($trait->isProduct($product));
    }

    /**
     * @test
     * @return void
     */
    public function can_fail_attributes_not_being_an_array()
    {
        $trait = new class {
            use ProductToolkit;
        };

        $product = [
            'name' => '{}name',
            'value' => [],
            'attributes' => '',
        ];

        $this->assertFalse($trait->isProduct($product));
    }

    /**
     * @test
     * @return void
     */
    public function can_fail_value_not_being_an_array_or_null()
    {
        $trait = new class {
            use ProductToolkit;
        };

        $product = [
            'name' => '{}name',
            'value' => '',
            'attributes' => [],
        ];

        $this->assertFalse($trait->isProduct($product));
    }

    /**
     * @test
     * @return void
     */
    public function can_have_value_as_null()
    {
        $trait = new class {
            use ProductToolkit;
        };

        $product = [
            'name' => '{}name',
            'value' => null,
            'attributes' => [],
        ];

        $this->assertTrue($trait->isProduct($product));
    }

    /**
     * @test
     * @return void
     */
    public function can_fail_value_as_null_attributes_as_not_array()
    {
        $trait = new class {
            use ProductToolkit;
        };

        $product = [
            'name' => '{}name',
            'value' => null,
            'attributes' => '',
        ];

        $this->assertFalse($trait->isProduct($product));
    }

    /**
     * @test
     * @return void
     */
    public function can_validate_arrays_of_products()
    {
        $trait = new class {
            use ProductToolkit;
        };

        $product = [
            [
                'name' => '{}name',
                'value' => [],
                'attributes' => [],
            ],
            [
                'name' => '{}name',
                'value' => [],
                'attributes' => [],
            ],
            [
                'name' => '{}name',
                'value' => [],
                'attributes' => [],
            ]
        ];

        $this->assertTrue($trait->isProductArray($product));
    }

        /**
     * @test
     * @return void
     */
    public function can_fail_arrays_of_products()
    {
        $trait = new class {
            use ProductToolkit;
        };

        $product = [
            [
                'name' => '{}name',
                'value' => [],
                'attributes' => [],
            ],
            [
                'name' => '{}name',
                'value' => '',
                'attributes' => [],
            ],
            [
                'name' => '{}name',
                'value' => [],
                'attributes' => [],
            ]
        ];

        $this->assertFalse($trait->isProductArray($product));
    }
}
