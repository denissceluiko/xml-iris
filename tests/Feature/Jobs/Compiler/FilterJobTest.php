<?php

namespace Tests\Feature\Jobs\Compiler;

use App\Jobs\Compiler\CompileJob;
use App\Jobs\Product\FilterJob;
use App\Models\CompiledProduct;
use App\Models\Compiler;
use App\Models\ProcessedProduct;
use App\Models\Processor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class FilterJobTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function will_not_compile_products_from_disabled_processors()
    {

        $ean = '9116963990362';
        $compiler = Compiler::factory()
            ->fields([
                'ean' => 'ean',
                'name' => 'string',
                'price' => 'float',
            ])
            ->rules('order("price", "asc")')
            ->has(Processor::factory()
                ->has(ProcessedProduct::factory()
                    ->state([
                        'ean' => $ean,
                        'transformed_data' => [
                            'ean' => $ean,
                            'name' => 'fake product 1',
                            'price' => 5.00,
                        ]
                    ])
                )
            )
            ->has(Processor::factory()
                ->has(ProcessedProduct::factory()
                    ->state([
                        'ean' => $ean,
                        'transformed_data' => [
                            'ean' => $ean,
                            'name' => 'fake product 2',
                            'price' => 4.50,
                        ]
                    ])
                )
            )
            ->has(Processor::factory()
                ->disabled()
                ->has(ProcessedProduct::factory()
                    ->state([
                        'ean' => $ean,
                        'transformed_data' => [
                            'ean' => $ean,
                            'name' => 'fake product 3',
                            'price' => 4.00,
                        ]
                    ])
                )
            )
            ->create();

        $compiler->upsertMissing($compiler->processedProducts()->get());

        Bus::fake();

        [$job, $batch] = (new FilterJob($compiler, $ean))->withFakeBatch();
        $job->handle();

        $compiled = CompiledProduct::where('ean', $ean)->first();

        $this->assertEquals([
            'ean' => $ean,
            'name' => 'fake product 2',
            'price' => 4.50,
        ], $compiled->data);
    }

    /**
     * @test
     */
    public function will_set_stock_to_zero_if_filtered_is_empty()
    {

        $ean = '9116963990362';
        $compiler = Compiler::factory()
            ->fields([
                'ean' => 'ean',
                'name' => 'string',
                'stock' => 'int',
            ])
            ->rules('order("stock", "desc")')
            ->has(Processor::factory()
                ->has(ProcessedProduct::factory()
                    ->state([
                        'ean' => $ean,
                        'transformed_data' => [
                            'ean' => $ean,
                            'name' => 'fake product 3',
                            'stock' => 3,
                        ]
                    ])
                )
            )
            ->create();

        $compiler->upsertMissing($compiler->processedProducts()->get());

        Bus::fake();

        [$job, $batch] = (new FilterJob($compiler, $ean))->withFakeBatch();
        $job->handle();

        $compiler->processors()->update([
            'enabled' => 0,
        ]);

        [$job, $batch] = (new FilterJob($compiler, $ean))->withFakeBatch();
        $job->handle();

        $this->assertDatabaseHas('compiled_products', [
                'ean' => $ean,
                'data->ean' => $ean,
                'data->stock' => 0,
                'stale_level' => -1,
        ]);
    }


    /**
     * @test
     */
    public function will_delete_nonexistent_products()
    {

        $ean = '9116963990362';
        $ean2 = '9116963990363';
        $compiler = Compiler::factory()
            ->fields([
                'ean' => 'ean',
                'name' => 'string',
                'stock' => 'int',
            ])
            ->rules('order("stock", "desc")')
            ->has(Processor::factory()
                ->has(ProcessedProduct::factory()
                    ->state([
                        'ean' => $ean,
                        'transformed_data' => [
                            'ean' => $ean,
                            'name' => 'fake product 3',
                            'stock' => 3,
                        ]
                    ])
                )
            )
            ->create();

        $processor2 = Processor::factory()
            ->for($compiler)
            ->has(ProcessedProduct::factory()
                ->state([
                    'ean' => $ean2,
                    'transformed_data' => [
                        'ean' => $ean2,
                        'name' => 'fake product 5',
                        'stock' => 6,
                    ]
                ])
            )->create();

        $compiler->upsertMissing($compiler->processedProducts()->get());

        Bus::fake();

        [$job, $batch] = (new FilterJob($compiler, $ean))->withFakeBatch();
        $job->handle();

        [$job2, $batch] = (new FilterJob($compiler, $ean2))->withFakeBatch();
        $job2->handle();

        $compiler->processors()
            ->whereNot('id', $processor2->id)
            ->update([
                'enabled' => -1,
            ]);

        $compiler->compiledProducts()->update([
            'stale_level' => -1,
        ]);

        [$job, $batch] = (new FilterJob($compiler, $ean))->withFakeBatch();
        $job->handle();

        [$job2, $batch] = (new FilterJob($compiler, $ean2))->withFakeBatch();
        $job2->handle();

        // Will not delete other things
        $this->assertDatabaseHas('compiled_products', [
            'ean' => $ean2,
            'data->ean' => $ean2,
            'data->stock' => 6,
            'stale_level' => 0,
        ]);

        $this->assertDatabaseMissing('compiled_products', [
            'ean' => $ean,
            'data->ean' => $ean,
            'data->stock' => 0,
            'stale_level' => -1,
        ]);
    }
}
