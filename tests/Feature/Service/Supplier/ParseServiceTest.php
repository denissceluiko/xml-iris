<?php

namespace Tests\Feature\Service\Supplier;

use App\Models\Supplier;
use App\Services\Supplier\ParseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Traits\CopyToImportDisk;

class ParseServiceTest extends TestCase
{
    use RefreshDatabase;

    public function setUp() : void
    {
        parent::setUp();

        config()->set('filesystems.disks.local.root', base_path('tests/data'));
    }

    /**
     * @test
     * @return void
     */
    public function can_parse_xml_file()
    {
        $supplier = Supplier::factory()
                        ->uri('this_file_dies_not_exist.xml')
                        ->structure([
                            "product" => [
                                "type" => "keyValue",
                                "value" => [
                                    "name" => "string",
                                ]
                            ]
                        ])
                        ->create();

        $path = sha1(date('dmyHis-test'));
        Storage::disk('import')->put($path, "<product><name>Test</name></product>");

        $parsed = (new ParseService($supplier))->parse($path);

        $this->assertEquals([
            "name" => [
                "name" => "{}name",
                "value" => "Test",
                "attributes" => []
            ],
        ], $parsed);

        Storage::disk('import')->delete($path);
    }

    /**
     * @test
     * @return void
     */
    public function can_not_parse_random_extension_file()
    {
        $supplier = Supplier::factory()
                        ->uri('this_file_dies_not_exist.yolo')
                        ->structure([])
                        ->create();

        $path = sha1(date('dmyHis-test'));
        Storage::disk('import')->put($path, "just some random string");

        $parsed = (new ParseService($supplier))->parse($path);

        $this->assertEquals([], $parsed);

        Storage::disk('import')->delete($path);
    }
}
