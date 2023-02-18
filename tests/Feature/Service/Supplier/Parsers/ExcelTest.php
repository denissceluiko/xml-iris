<?php

namespace Tests\Feature\Service\Supplier\Parsers;

use App\Models\Supplier;
use App\Services\Supplier\Parsers\Excel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Traits\CopyToImportDisk;

class ExcelTest extends TestCase
{
    use RefreshDatabase, CopyToImportDisk;

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
    public function can_import_an_excel_file()
    {
        $supplier = Supplier::factory()->config([
            'root_tag' => 'none',
            'product_tag' => 'none',
            'source_type' => 'excel',
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

    $parser = new Excel($supplier);

    $result = $parser->parse($this->copyToImport('supplier_import_simple.xlsx'));

    $this->assertEquals([
            [
                'name' => '{}none',
                'attributes' => [],
                'value' => [
                    [
                        "name" => "{}sku",
                        "value" => 7426825373410,
                        "attributes" => [],
                    ],
                    [
                        "name" => "{}gtin",
                        "value" => 7426825373410,
                        "attributes" => [],
                    ],
                    [
                        "name" => "{}price_lt",
                        "value" => 1,
                        "attributes" => [],
                    ],
                    [
                        "name" => "{}price_after_discount_lt",
                        "value" => null,
                        "attributes" => [],
                    ],
                    [
                        "name" => "{}price_lv",
                        "value" => 1,
                        "attributes" => [],
                    ],
                    [
                        "name" => "{}price_after_discount_lv",
                        "value" => null,
                        "attributes" => [],
                    ],
                    [
                        "name" => "{}price_ee",
                        "value" => 1,
                        "attributes" => [],
                    ],
                    [
                        "name" => "{}price_after_discount_ee",
                        "value" => null,
                        "attributes" => [],
                    ],
                    [
                        "name" => "{}stock",
                        "value" => 0,
                        "attributes" => [],
                    ],
                    [
                        "name" => "{}delivery_hours",
                        "value" => 24,
                        "attributes" => [],
                    ],
                ],
            ],
            [
                'name' => '{}none',
                'attributes' => [],
                'value' => [
                    [
                        "name" => "{}sku",
                        "value" => 7426825365507,
                        "attributes" => [],
                    ],
                    [
                        "name" => "{}gtin",
                        "value" => 7426825365507,
                        "attributes" => [],
                    ],
                    [
                        "name" => "{}price_lt",
                        "value" => 3.95,
                        "attributes" => [],
                    ],
                    [
                        "name" => "{}price_after_discount_lt",
                        "value" => null,
                        "attributes" => [],
                    ],
                    [
                        "name" => "{}price_lv",
                        "value" => 3.95,
                        "attributes" => [],
                    ],
                    [
                        "name" => "{}price_after_discount_lv",
                        "value" => null,
                        "attributes" => [],
                    ],
                    [
                        "name" => "{}price_ee",
                        "value" => 3.95,
                        "attributes" => [],
                    ],
                    [
                        "name" => "{}price_after_discount_ee",
                        "value" => null,
                        "attributes" => [],
                    ],
                    [
                        "name" => "{}stock",
                        "value" => 2,
                        "attributes" => [],
                    ],
                    [
                        "name" => "{}delivery_hours",
                        "value" => 24,
                        "attributes" => [],
                    ],
                ]
            ],
        ], $result);

    }
}
