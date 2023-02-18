<?php

namespace Tests\Feature\Service\Supplier;

use App\Models\Supplier;
use App\Services\Supplier\ParseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Traits\CopyToImportDisk;
use Tests\Traits\ProductToolkit;

class ParseServiceTest extends TestCase
{
    use RefreshDatabase, CopyToImportDisk, ProductToolkit;

    public function setUp() : void
    {
        parent::setUp();

        config()->set('filesystems.disks.local.root', base_path('tests/data'));
        config()->set('filesystems.disks.import.root', base_path('tests/data/import'));
    }

    /**
     * @test
     * @return void
     */
    public function can_parse_xml_file()
    {
        $supplier = Supplier::factory()
                        ->uri('supplier_import_simple.xml')
                        ->structure([
                            "products" => [
                                "type" => "repeatingElements",
                                "child" => "product",
                                "value" => [
                                    "product" => [
                                        "type" => "keyValue",
                                        "value" => [
                                            "ean" => "string",
                                            "name" => "string",
                                            "stock" => "integer",
                                            "currency" => "currencyCode",
                                            "price" => "float"
                                        ]
                                    ]
                                ]
                            ]
                        ])
                        ->create();

        $path = $this->copyToImport($supplier->uri);
        $parsed = (new ParseService($supplier))->parse($path);

        $this->assertTrue($this->isProductArray($parsed));

        Storage::disk('import')->delete($path);
    }

        /**
     * @test
     * @return void
     */
    public function can_parse_excel_file()
    {
        $supplier = Supplier::factory()
                        ->uri('supplier_import_simple.xlsx')
                        ->config([
                            'source_type' => 'excel'
                        ])
                        ->structure([
                            "sku" => "sku",
                            "gtin" => "gtin",
                            "price_lt" => "PRICE LT",
                            "price_after_discount_lt" => "Price LT after discount",
                            "price_lv" => "PRICE LV",
                            "price_after_discount_lv" => "Price LV after discount",
                            "price_ee" => "PRICE EE",
                            "price_after_discount_ee" => "Price EE after discount",
                            "stock" => "stock",
                            "delivery_hours" => "Delivery hours",
                          ])
                        ->create();

        $path = $this->copyToImport($supplier->uri);

        $parsed = (new ParseService($supplier))->parse($path);

        $this->assertCount(2, $parsed);
        $this->assertTrue($this->isProductArray($parsed));

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

        $this->assertEquals(null, $parsed);

        Storage::disk('import')->delete($path);
    }
}
