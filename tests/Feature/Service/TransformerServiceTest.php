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
            'expression' => 'price*3',
        ], [
            'sku' => 'string',
            'price' => 'float',
            'stock' => 'int',
            'expression' => 'string',
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
            'expression' => 2.82,
        ], $result);
    }

    /**
     * @test
     * @return void
     */
    public function can_handle_simple_expression()
    {
        $transformer = new TransformerService([
            'sku' => 'sku',
            'price' => 'price',
            'stock' => 'stock',
            'expression' => '["price", "<", "1.00", "price*3", "price"]',
        ], [
            'sku' => 'string',
            'price' => 'float',
            'stock' => 'int',
            'expression' => 'string',
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
            'expression' => 2.82,
        ], $result);
    }

    /**
     * @test
     * @return void
     */
    public function can_handle_simple_false_expression()
    {
        $transformer = new TransformerService([
            'sku' => 'sku',
            'price' => 'price',
            'stock' => 'stock',
            'expression' => '["price", "<", "1.00", "price*3", "price"]',
        ], [
            'sku' => 'string',
            'price' => 'float',
            'stock' => 'int',
            'expression' => 'string',
        ], [
            'sku' => '101010',
            'price' => '5.94',
            'stock' => '123',
        ]);

        $result = $transformer->transform();

        $this->assertEquals([
            'sku' => '101010',
            'price' => 5.94,
            'stock' => 123,
            'expression' => 5.94,
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
