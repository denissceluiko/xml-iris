<?php

namespace Tests\Feature\Service\Supplier;

use App\Models\Supplier;
use App\Services\Supplier\Parsers\XmlParser;
use App\Services\Supplier\ParseService;
use App\Traits\ProductToolkit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Traits\CopyToImportDisk;

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
                        ->config([
                            'root_tag' => 'products',
                            'product_tag' => 'product',
                        ])
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
        $parser = (new ParseService($supplier, $path))->getParser();

        $this->assertTrue($parser instanceof XmlParser);

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
                            "ean" => "gtin",
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

        (new ParseService($supplier, $path))->getParser()->parse();

        $this->assertDatabaseCount('products', 2);

        Storage::disk('import')->delete($path);
    }

    /**
     * @test
     * @return void
     */
    public function can_parse_csv_file()
    {
        $supplier = Supplier::factory()
                        ->uri('supplier_import_simple.csv')
                        ->config([
                            'source_type' => 'csv'
                        ])
                        ->structure([
                            "sku" => "sku",
                            "ean" => "gtin",
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

        (new ParseService($supplier, $path))->getParser()->parse();

        $this->assertDatabaseCount('products', 2);

        Storage::disk('import')->delete($path);
    }
    /**
     * @test
     * @return void
     */
    public function can_not_parse_random_extension_file()
    {
        $supplier = Supplier::factory()
                        ->uri('this_file_can_not_be_parsed.yolo')
                        ->structure([])
                        ->create();

        $parser = (new ParseService($supplier, 'this_file_can_not_be_parsed.yolo'))->getParser();

        $this->assertEquals(null, $parser);
    }
}
