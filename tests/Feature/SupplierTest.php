<?php

namespace Tests\Feature;

use App\Jobs\SupplierPull;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SupplierTest extends TestCase
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
    public function can_dispatch_pull_job()
    {
        Bus::fake();

        $supplier = Supplier::factory()
                        ->uri(Storage::path('supplier_import_simple.xml'))
                        ->create();
        $supplier->pull();

        Bus::assertDispatched(SupplierPull::class);

    }

    /**
     * @test
     * @return boolean
     */
    public function will_not_dispatch_pull_job_when_uri_not_configured()
    {
        Bus::fake();

        // `uri` is not nullable in the DB but also can't fail empty() check
        $supplier = Supplier::factory()
                        ->uri('')
                        ->create();

        $supplier->pull();

        Bus::assertNotDispatched(SupplierPull::class);
    }

    /**
     * @test
     * @return boolean
     */
    public function will_not_dispatch_pull_job_when_config_not_configured()
    {
        Bus::fake();

        // `uri` is not nullable in the DB but also can't fail empty() check
        $supplier = Supplier::factory()
                        ->uri(Storage::path('supplier_import_simple.xml'))
                        ->config([])
                        ->create();

        $supplier->pull();

        Bus::assertNotDispatched(SupplierPull::class);
    }

    /**
     * @test
     * @return boolean
     */
    public function will_not_dispatch_pull_job_when_config_not_fully_configured()
    {
        Bus::fake();

        // `uri` is not nullable in the DB but also can't fail empty() check
        $supplier = Supplier::factory()
                        ->uri(Storage::path('supplier_import_simple.xml'))
                        ->config([
                            'product_tag' => 'product',
                            'source_type' => 'xml',
                        ])
                        ->create();

        $supplier->pull();

        Bus::assertNotDispatched(SupplierPull::class);
    }

    /**
     * @test
     * @return boolean
     */
    public function will_not_dispatch_pull_job_when_structure_not_configured()
    {
        Bus::fake();

        // `uri` is not nullable in the DB but also can't fail empty() check
        $supplier = Supplier::factory()
                        ->uri(Storage::path('supplier_import_simple.xml'))
                        ->config([
                            'root_tag' => 'products',
                            'product_tag' => 'product',
                            'source_type' => 'xml',
                        ])
                        ->structure([])
                        ->create();

        $supplier->pull();

        Bus::assertNotDispatched(SupplierPull::class);
    }
}
