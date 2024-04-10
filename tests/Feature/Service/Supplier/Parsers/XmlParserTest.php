<?php

namespace Tests\Feature\Service\Supplier\Parsers;

use App\Jobs\XmlSupplierParseJob;
use App\Models\Supplier;
use App\Services\Supplier\Parsers\XmlParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Traits\CopyToImportDisk;

class XmlParserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic supplier parse test.
     *
     * @test
     * @return void
     */
    public function can_dispatch_xml_supplier_parse_job()
    {

        $supplier = Supplier::factory()
                        ->uri('supplier_import_simple.xml')
                        ->create();

        Bus::fake();

        $parser = new XmlParser($supplier, Storage::path($supplier->uri));
        $parser->parse();

        Bus::assertDispatched(XmlSupplierParseJob::class);
    }

}
