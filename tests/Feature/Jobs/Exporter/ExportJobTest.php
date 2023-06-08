<?php

namespace Tests\Feature\Jobs\Exporter;

use App\Jobs\Exporter\ExportJob;
use App\Jobs\Exporter\Xml\CompiledProductExportJob;
use App\Models\Compiler;
use App\Models\Export;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Bus;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;
use Throwable;

class ExportJobTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @return void
     */
    public function can_export_excel()
    {
        Excel::fake();

        $compiler = Compiler::factory()->create();

        $export = Export::factory()
                        ->compiler($compiler)
                        ->excel()
                        ->create();

        [$job, $batch] = (new ExportJob($export))->withFakeBatch();

        $job->handle();

        Excel::matchByRegex();

        // For a given dynamic named file 'export_{sha1 hash}.xlsx'
        Excel::assertStored('/export_\w{40}\.xlsx/', 'export');
    }

    /**
     * @test
     * @return void
     */
    public function can_export_xml()
    {
        Bus::fake();

        $compiler = Compiler::factory()->create();

        $export = Export::factory()
                        ->compiler($compiler)
                        ->xml()
                        ->create();

        [$job, $batch] = (new ExportJob($export))->withFakeBatch();

        $job->handle();

        Bus::assertDispatched(CompiledProductExportJob::class);
    }

    /**
     * @test
     * @return void
     */
    public function will_fail_nonexistent_exporter()
    {
        $compiler = Compiler::factory()->create();

        $export = Export::factory()
                        ->compiler($compiler)
                        ->type('nonexistent')
                        ->create();

        [$job, $batch] = (new ExportJob($export))->withFakeBatch();

        try {
            $job->handle();
        } catch (Throwable $e) {
            $this->fail("Tried exporting using a nonexistent exporter");
        }

        $this->assertTrue(true);
    }
}
