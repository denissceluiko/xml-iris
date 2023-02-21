<?php

namespace Tests\Feature\Jobs\Exporter\XML;

use App\Jobs\Exporter\Xml\CompiledProductExportJob;
use App\Models\Export;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CompiledProductExportJobTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @return void
     */
    public function can_export_xml()
    {
        $filename = 'xml-export.xml';

        $export = Export::factory()
                    ->xml()
                    ->create();

        $job = new CompiledProductExportJob($export, $filename, 'export');

        $job->handle();

        $this->assertTrue(Storage::disk('export')->exists($filename));

        $expected =<<<EOF
<?xml version="1.0" encoding="UTF-8"?>


EOF;
        $this->assertEquals($expected, Storage::disk('export')->get($filename));

        Storage::disk('export')->delete($filename);
    }
}
