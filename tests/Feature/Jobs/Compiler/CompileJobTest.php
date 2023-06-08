<?php

namespace Tests\Feature\Jobs\Compiler;

use App\Jobs\Compiler\CompileJob;
use App\Models\Compiler;
use App\Models\ProcessedProduct;
use App\Models\Processor;
use App\Models\Supplier;
use Illuminate\Bus\PendingBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class CompileJobTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @return void
     */
    public function will_not_compile_empty_compiler()
    {

        $compiler = Compiler::factory()
            ->fields([
                'ean' => 'ean',
                'name' => 'string',
                'price' => 'float',
            ])
            ->create();

        Bus::fake();

        [$job, $batch] = (new CompileJob($compiler))->withFakeBatch();
        $job->handle();

        Bus::assertNothingBatched();
    }

    /**
     * Will create batch compilers and upsert missing compiled products
     * 
     * @test
     * @return void
     */
    public function will_dispatch_compiler_batch_master_processor()
    {
        $compiler = Compiler::factory()
            ->fields([
                'ean' => 'ean',
                'name' => 'string',
                'price' => 'float',
            ])
            ->has(Processor::factory()
                ->count(2)
                ->has(ProcessedProduct::factory()
                    ->count(10)
                )
            )
            ->create();

        Bus::fake();

        [$job, $batch] = (new CompileJob($compiler))->withFakeBatch();
        $job->handle();

        // Checks if missing records are upserted
        $this->assertDatabaseCount('compiled_products', 20);

        Bus::assertBatched(function (PendingBatch $batch) {
            return $batch->name == 'Compile products master' &&
                   $batch->queue() == 'default' &&
                   $batch->jobs->count() == 1;
        });
    }
}
