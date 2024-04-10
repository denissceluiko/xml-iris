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
}
