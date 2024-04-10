<?php

namespace Tests\Feature\Jobs;

use App\Jobs\Processor\ProcessOrphanedProducts;
use App\Models\Compiler;
use App\Models\Processor;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Bus\PendingBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class ProcessOrphanedProductsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     *
     * @return void
     */
    public function can_process_orphaned_products()
    {
        $fields = [
            'ean' => 'string',
            'stock' => 'int',
            'delivery_time' => 'int',
        ];

        $time = Carbon::now();

        $supplier = Supplier::factory()
            ->hasProducts(5, [
                'last_pulled_at' => $time->copy()->addMinutes(5),
            ]) // Fresh
            ->hasProducts(5, [
                'last_pulled_at' => $time->copy()->subMinutes(5),
            ]) // Orphaned
            ->pulledAt($time)
            ->create();

        $compiler = Compiler::factory()
            ->fields($fields)
            ->create();

        $processor = Processor::factory()
            ->for($compiler)
            ->for($supplier)
            ->create();

        $processor->upsertMissing();
        $this->assertDatabaseCount('processed_products', 10);

        Bus::fake();

        $job = new ProcessOrphanedProducts($supplier);
        $job->handle();

        Bus::assertBatchCount(1);
        Bus::assertBatched(function (PendingBatch $batch) {
            return $batch->jobs->count() == 5 &&
                   $batch->name == 'Orphaned product processing';
        });
    }

    /**
     * @test
     *
     * @return void
     */
    public function will_fail_if_supplier_wasnt_pulled()
    {
        $supplier = Supplier::factory()
            ->create();

        Bus::fake();

        $job = new ProcessOrphanedProducts($supplier);
        $job->handle();

        Bus::assertNothingBatched();
    }
}
