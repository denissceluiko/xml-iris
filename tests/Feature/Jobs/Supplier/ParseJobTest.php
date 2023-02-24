<?php

namespace Tests\Feature\Jobs\Supplier;

use App\Jobs\Supplier\ParseJob;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
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

    public function can_route_parse_job_to_xml_handler()
    {

    }
}
