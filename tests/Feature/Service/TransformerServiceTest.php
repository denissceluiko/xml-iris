<?php

namespace Tests\Feature\Service;

use App\Services\Processor\TransformerService;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TransformerServiceTest extends TestCase
{
    /**
     * @test
     * @return void
     */
    public function can_transform_field_name_to_its_value()
    {
        $transformer = new TransformerService([
            'sku' => 'sku',
            'price' => 'price',
            'stock' => 'stock',
        ], [
            'sku' => 'string',
            'price' => 'float',
            'stock' => 'int',
        ], [
            'sku' => '101010',
            'price' => '0.94',
            'stock' => '123',
        ]);

        $result = $transformer->transform();

        $this->assertEquals([
            'sku' => '101010',
            'price' => 0.94,
            'stock' => 123,
        ], $result);
    }

        /**
     * @test
     * @return void
     */
    public function will_round_floats_to_two_precision()
    {
        $transformer = new TransformerService([
            'sku' => 'sku',
            'price' => 'price',
            'stock' => 'stock',
        ], [
            'sku' => 'string',
            'price' => 'float',
            'stock' => 'int',
        ], [
            'sku' => '101010',
            'price' => '0.9469',
            'stock' => '123',
        ]);

        $result = $transformer->transform();

        $this->assertEquals([
            'sku' => '101010',
            'price' => 0.95,
            'stock' => 123,
        ], $result);
    }

    /**
     * @test
     * @return void
     */
    public function can_handle_new_computed_fields()
    {
        $transformer = new TransformerService([
            'sku' => 'sku',
            'price' => 'price',
            'stock' => 'stock',
            'compound' => 'stock @ price',
        ], [
            'sku' => 'string',
            'price' => 'float',
            'stock' => 'int',
            'compound' => 'string',
        ], [
            'sku' => '101010',
            'price' => '0.94',
            'stock' => '123',
        ]);

        $result = $transformer->transform();

        $this->assertEquals([
            'sku' => '101010',
            'price' => 0.94,
            'stock' => 123,
            'compound' => '123 @ 0.94',
        ], $result);
    }

    /**
     * @test
     * @return void
     */
    public function can_handle_maths_expressions()
    {
        $transformer = new TransformerService([
            'sku' => 'sku',
            'price' => 'price',
            'stock' => 'stock',
            'expression_one' => 'price*3',
            'expression_two' => 'price*stock',
        ], [
            'sku' => 'string',
            'price' => 'float',
            'stock' => 'int',
            'expression_one' => 'string',
            'expression_two' => 'string',
        ], [
            'sku' => '101010',
            'price' => '0.94',
            'stock' => '123',
        ]);

        $result = $transformer->transform();

        $this->assertEquals([
            'sku' => '101010',
            'price' => 0.94,
            'stock' => 123,
            'expression_one' => 2.82,
            'expression_two' => 115.62,
        ], $result);
    }

    /**
     * @test
     * @return void
     */
    public function can_handle_comparisons()
    {
        $transformer = new TransformerService([
            'sku' => 'sku',
            'price' => 'price',
            'stock' => 'stock',
            'expression_lt' => '["price", "<", "1.00", "price*3", "price"]',
            'expression_eq' => '["price", "=", "0.94", "price*2", "price"]',
            'expression_gt' => '["price", ">", "0.50", "price", "price*3"]',
        ], [
            'sku' => 'string',
            'price' => 'float',
            'stock' => 'int',
            'expression_lt' => 'string',
            'expression_eq' => 'string',
            'expression_gt' => 'string',
        ], [
            'sku' => '101010',
            'price' => '0.94',
            'stock' => '123',
        ]);

        $result = $transformer->transform();

        $this->assertEquals([
            'sku' => '101010',
            'price' => 0.94,
            'stock' => 123,
            'expression_lt' => 2.82,
            'expression_eq' => 1.88,
            'expression_gt' => 0.94,
        ], $result);
    }

    /**
     * @test
     * @return void
     */
    public function can_handle_false_equivalents_as_input_data_for_expressions()
    {
        $transformer = new TransformerService([
            'price' => 'price',
            'stock' => 'stock',
            'expression_one' => '["price", "<", "1.00", "price*3+1.25", "price"]',
            'expression_two' => '["stock", "=", "0", "stock", "3"]',
        ], [
            'price' => 'float',
            'stock' => 'int',
            'expression_one' => 'float',
            'expression_two' => 'int',
        ], [
            'price' => '0',
            'stock' => null,
        ]);

        $result = $transformer->transform();

        $this->assertEquals([
            'price' => 0.0,
            'stock' => 0,
            'expression_one' => 1.25,
            'expression_two' => 0,
        ], $result);
    }

    /**
     * @test
     * @return void
     */
    public function can_handle_nested_expression()
    {
        $transformer = new TransformerService([
            'sku' => 'sku',
            'price' => 'price',
            'stock' => 'stock',
            'expression' => '["price", "<", "1.00", "price*3", ["price", "<", "2.00", "price*2.5", "price"]]',
        ], [
            'sku' => 'string',
            'price' => 'float',
            'stock' => 'int',
            'expression' => 'string',
        ], [
            'sku' => '101010',
            'price' => '1.94',
            'stock' => '123',
        ]);

        $result = $transformer->transform();

        $this->assertEquals([
            'sku' => '101010',
            'price' => 1.94,
            'stock' => 123,
            'expression' => 4.85,
        ], $result);
    }
}
