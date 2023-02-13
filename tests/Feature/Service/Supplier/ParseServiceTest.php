<?php

namespace Tests\Feature\Service\Supplier;

use App\Models\Supplier;
use App\Services\Supplier\ParseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

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

        $parsed = (new ParseService($supplier))->parse("<product><name>Test</name></product>");

        $this->assertEquals([
            "name" => [
                "name" => "{}name",
                "value" => "Test",
                "attributes" => []
            ],
        ], $parsed);

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

        $parsed = (new ParseService($supplier))->parse("just some random string");

        $this->assertEquals([], $parsed);
    }
}
