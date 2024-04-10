<?php

namespace Tests\Feature\Jobs\Compiler;

use App\Jobs\Compiler\CompileBatchJob;
use App\Models\CompiledProduct;
use App\Models\Compiler;
use Illuminate\Bus\PendingBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class CompileBatchJobTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Will create batch compilers and upsert missing compiled products
     *
     * @test
     * @return void
     */
    public function will_compile_and_dispactch_a_batch_of_filter_jobs()
    {
        $compiler = Compiler::factory()
            ->fields([
                'ean' => 'ean',
                'name' => 'string',
                'price' => 'float',
            ])
            ->has(CompiledProduct::factory()
                ->count(10)
            )
            ->create();

        Bus::fake();

        [$job, $batch] = (new CompileBatchJob($compiler, 0, 10))->withFakeBatch();
        $job->handle();

        Bus::assertBatched(function (PendingBatch $batch) {
            return $batch->name == 'Compile product batch' &&
                   $batch->queue() == 'default' &&
                   $batch->jobs->count() == 10;
        });
    }
}
