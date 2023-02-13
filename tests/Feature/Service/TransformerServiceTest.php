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
}
