<?php

namespace Tests\Feature\Services\Compiler;

use App\Models\Compiler;
use App\Models\ProcessedProduct;
use App\Services\Compiler\FilterService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class FilterServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @return void
     */
    public function can_handle_empty_product_array()
    {

        $fields = [
            'ean' => 'string',
            'stock' => 'int',
            'delivery_time' => 'int',
        ];

        $compiler = Compiler::factory()
                        ->fields($fields)
                        ->create();

        $processedProducts = Collection::make([]);

        $service = new FilterService($compiler);

        $filtered = $service->filter($processedProducts);

        $this->assertTrue($filtered === null);
    }

    /**
     * @test
     * @return void
     */
    public function can_filter_products_with_no_rules()
    {

        $fields = [
            'ean' => 'string',
            'stock' => 'int',
            'delivery_time' => 'int',
        ];

        $compiler = Compiler::factory()
                        ->fields($fields)
                        ->create();

        $processedProducts = ProcessedProduct::factory()
                                ->count(3)
                                ->make();

        $service = new FilterService($compiler);

        $filtered = $service->filter($processedProducts);

        $this->assertTrue($filtered instanceof ProcessedProduct);
    }

    /**
     * @test
     * @return void
     */
    public function can_filter_products_with_order_rule()
    {

        $fields = [
            'ean' => 'string',
            'stock' => 'int',
            'delivery_time' => 'int',
        ];

        $compiler = Compiler::factory()
                        ->fields($fields)
                        ->rules('order("delivery_time", "desc")')
                        ->create();

        $processedProducts = ProcessedProduct::factory()
                                ->count(3)
                                ->state(new Sequence(
                                    ['transformed_data' => ['delivery_time' => 12]],
                                    ['transformed_data' => ['delivery_time' => 24]],
                                    ['transformed_data' => ['delivery_time' => 36]],
                                ))
                                ->make();

        $service = new FilterService($compiler);

        $filtered = $service->filter($processedProducts);

        $this->assertTrue($filtered instanceof ProcessedProduct);
        $this->assertEquals(36, $filtered->transformed_data['delivery_time']);
    }

    /**
     * @test
     * @return void
     */
    public function can_filter_products_with_dropif_rule()
    {

        $fields = [
            'ean' => 'string',
            'stock' => 'int',
            'delivery_time' => 'int',
        ];

        $compiler = Compiler::factory()
                        ->fields($fields)
                        ->rules('order("delivery_time", "asc")->dropIf("stock", "<", "1")')
                        ->create();

        $processedProducts = ProcessedProduct::factory()
                                ->count(3)
                                ->state(new Sequence(
                                    ['transformed_data' => ['delivery_time' => 12, 'stock' => 0]],
                                    ['transformed_data' => ['delivery_time' => 24, 'stock' => 43]],
                                    ['transformed_data' => ['delivery_time' => 24, 'stock' => 15]],
                                ))
                                ->make();

        $service = new FilterService($compiler);

        $filtered = $service->filter($processedProducts);

        $this->assertTrue($filtered instanceof ProcessedProduct);
        $this->assertEquals(43, $filtered->transformed_data['stock']);
        $this->assertEquals(24, $filtered->transformed_data['delivery_time']);
    }

    /**
     * @test
     * @return void
     */
    public function can_filter_products_with_compound_rules()
    {

        $fields = [
            'ean' => 'string',
            'stock' => 'int',
            'delivery_time' => 'int',
        ];

        $compiler = Compiler::factory()
                        ->fields($fields)
                        ->rules('dropIf("stock", "<", "1")')
                        ->create();

        $processedProducts = ProcessedProduct::factory()
                                ->count(3)
                                ->state(new Sequence(
                                    ['transformed_data' => ['stock' => 0]],
                                    ['transformed_data' => ['stock' => 24]],
                                    ['transformed_data' => ['stock' => 36]],
                                ))
                                ->make();

        $service = new FilterService($compiler);

        $filtered = $service->filter($processedProducts);

        $this->assertTrue($filtered instanceof ProcessedProduct);
        $this->assertEquals(24, $filtered->transformed_data['stock']);
    }
}
