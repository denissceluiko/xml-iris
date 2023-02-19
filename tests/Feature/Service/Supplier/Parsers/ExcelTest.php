<?php

namespace Tests\Feature\Service\Supplier\Parsers;

use App\Models\Supplier;
use App\Services\Supplier\Parsers\ExcelParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Maatwebsite\Excel\Facades\Excel;
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

    public function tearDown() : void
    {
        $this->purgeCopies();

        parent::tearDown();
    }

    /**
     * @test
     * @return void
     */
    public function can_import_an_excel_file()
    {
        Excel::fake();

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

    $path = $this->copyToImport('supplier_import_simple.xlsx');

    $parser = new ExcelParser($supplier, $path);
    $result = $parser->parse();

    Excel::assertImported($path);

    }
}
