<?php

namespace Tests\Feature\Jobs\Supplier;

use App\Jobs\Processor\ProcessOrphanedProducts;
use App\Jobs\Supplier\CleanupJob;
use App\Jobs\Supplier\ParseJob;
use App\Jobs\XmlSupplierParseJob;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;
use Tests\Traits\CopyToImportDisk;

class ParseJobTest extends TestCase
{
    use RefreshDatabase, CopyToImportDisk;

    public function setUp() : void
    {
        parent::setUp();

        config()->set('filesystems.disks.local.root', base_path('tests/data'));
        config()->set('filesystems.disks.import.root', base_path('tests/data/import'));
    }

    public function tearDown() : void
    {
        $this->purgeCopies();

        parent::tearDown();
    }

    /**
     * @test
     * @return void
     */
    public function can_route_parse_job_to_excel_handler()
    {
        Excel::fake();

        $supplier = Supplier::factory()
            ->uri('supplier_import_simple.xlsx')
            ->config([
                'source_type' => 'excel'
            ])
            ->create();

        $path = $this->copyToImport($supplier->uri);

        $job = new ParseJob($supplier, $path);

        $job->handle();

        Excel::assertQueued(Storage::disk('import')->path($path));
    }

    /**
     * @test
     * @return void
     */
    public function can_route_parse_job_to_csv_handler()
    {
        Excel::fake();

        $supplier = Supplier::factory()
            ->uri('supplier_import_simple.csv')
            ->config([
                'source_type' => 'csv'
            ])
            ->create();

        $path = $this->copyToImport($supplier->uri);

        $job = new ParseJob($supplier, $path);

        $job->handle();

        Excel::assertQueued(Storage::disk('import')->path($path));

        /**
         * This is impossible to test now unfortunately.
         * ExcelFake::assertQueuedWithChain() tries looking up an anonymous class
         * in the fake Queue which does not seem to work out. Further testing is needed.
         */
        // Excel::assertQueuedWithChain([
        //     new CleanupJob($path),
        // ]);
    }

    /**
     * @test
     * @return void
     */
    public function can_route_parse_job_to_xml_handler()
    {
        Bus::fake();

        $supplier = Supplier::factory()
            ->uri('supplier_import_simple.xml')
            ->config([
                'source_type' => 'xml'
            ])
            ->create();

        $path = $this->copyToImport($supplier->uri);

        $job = new ParseJob($supplier, $path);

        $job->handle();

        Bus::assertChained([
            XmlSupplierParseJob::class,
            CleanupJob::class,
            ProcessOrphanedProducts::class,
        ]);
    }

    /**
     * @test
     * @return void
     */
    public function will_not_route_if_handler_does_not_exist()
    {
        Bus::fake();

        $supplier = Supplier::factory()
            ->uri('supplier_import_simple.xml')
            ->config([
                'source_type' => 'null'
            ])
            ->create();

        $path = $this->copyToImport($supplier->uri);

        $job = new ParseJob($supplier, $path);

        $job->handle();

        Bus::assertNothingDispatched();
    }
}
