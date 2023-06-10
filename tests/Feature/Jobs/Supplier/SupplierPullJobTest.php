<?php

namespace Tests\Feature\Supplier;

use App\Jobs\Supplier\ParseJob;
use App\Jobs\SupplierPull;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SupplierPullJobTest extends TestCase
{
    use RefreshDatabase;

    public function setUp() : void
    {
        parent::setUp();

        config()->set('filesystems.disks.local.root', base_path('tests/data'));
    }

    /**
     * @test
     * @return boolean
     */
    public function can_dispatch_parse_job()
    {
        Bus::fake();

        $supplier = Supplier::factory()
                        ->uri('supplier_import_simple.xml')
                        ->create();

        [$job, $batch] = (new SupplierPull($supplier))->withFakeBatch();
        $job->handle();

        Bus::assertDispatched(ParseJob::class);
        $this->assertDatabaseHas('suppliers', [
            'last_pulled_at' => Carbon::now(),
        ]);
    }

    /**
     * @test
     * @return boolean
     */
    public function will_not_dispatch_pull_job_when_uri_not_configured()
    {
        // `uri` is not nullable in the DB but also can't fail empty() check
        $supplier = Supplier::factory()
                        ->uri('')
                        ->create();

        $job = new SupplierPull($supplier);
        $this->assertFalse($job->canPull());
    }

    /**
     * @test
     * @return boolean
     */
    public function will_not_dispatch_pull_job_when_config_not_configured()
    {
        $supplier = Supplier::factory()
                        ->uri('supplier_import_simple.xml')
                        ->config([])
                        ->create();

        $job = new SupplierPull($supplier);
        $this->assertFalse($job->canPull());
    }

    /**
     * @test
     * @return boolean
     */
    public function will_not_dispatch_pull_job_when_config_not_fully_configured()
    {
        $supplier = Supplier::factory()
                        ->uri('supplier_import_simple.xml')
                        ->config([
                            'product_tag' => 'product',
                            'source_type' => 'xml',
                        ])
                        ->create();

        $job = new SupplierPull($supplier);
        $this->assertFalse($job->canPull());
    }

    /**
     * @test
     * @return boolean
     */
    public function will_not_dispatch_pull_job_when_structure_not_configured()
    {
        $supplier = Supplier::factory()
                        ->uri('supplier_import_simple.xml')
                        ->config([
                            'root_tag' => 'products',
                            'product_tag' => 'product',
                            'source_type' => 'xml',
                        ])
                        ->structure([])
                        ->create();

        $job = new SupplierPull($supplier);
        $this->assertFalse($job->canPull());
    }

    /**
     * @test
     * @return boolean
     */
    public function will_fail_if_pull_failed()
    {
        Bus::fake();
        Http::fake([
            'example.com/503_response.xml' => Http::response("Service unavailable", 503),
        ]);

        $supplier = Supplier::factory()
                        ->uri('https://example.com/503_response.xml')
                        ->create();

        [$job, $batch] = (new SupplierPull($supplier))->withFakeBatch();
        $job->handle();

        Bus::assertNotDispatched(ParseJob::class);
    }
}
