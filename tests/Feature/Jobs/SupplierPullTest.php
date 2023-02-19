<?php

namespace Tests\Feature\Jobs;

use App\Jobs\SupplierPull;
use App\Models\Product;
use App\Models\Supplier;
use App\Traits\ProductToolkit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SupplierPullTest extends TestCase
{
    use RefreshDatabase, ProductToolkit;

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
    public function can_do_supplier_pull()
    {
        $supplier = Supplier::factory()
                        ->uri('supplier_import_simple.xml')
                        ->config([
                            'root_tag' => 'products',
                            'product_tag' => 'product',
                            'source_type' => 'xml',
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

        $job = new SupplierPull($supplier);

        $job->handle();

        $this->assertDatabaseHas('products', [
            'ean' => '0000000000000',
            'supplier_id' => $supplier->id,
        ]);

        $product = Product::first();
        $this->assertTrue($this->isProductArray([$product->values]));
    }

    /**
     * @test
     * @return void
     */
    public function can_do_supplier_pull_with_a_large_file()
    {
        $supplier = Supplier::factory()
                        ->uri('supplier_import_large.xml')
                        ->config([
                            'root_tag' => 'product_list',
                            'product_tag' => 'product',
                            'source_type' => 'xml',
                        ])
                        ->structure([
                            "products" => [
                              "type" => "keyValue",
                              "value" => [
                                "link" => "url",
                                "title" => "string",
                                "description" => "string",
                                "product_list" => [
                                  "type" => "repeatingElements",
                                  "child" => "product",
                                  "value" => [
                                    "type" => "keyValue",
                                    "value" => [
                                      "ean" => "ean",
                                      "link" => "url",
                                      "name" => "string",
                                      "stock" => "int",
                                      "symbol" => "string",
                                      "price_list" => [
                                        "type" => "keyValue",
                                        "value" => [
                                          "netto" => "float",
                                          "brutto" => "float",
                                          "currency" => "string",
                                        ],
                                      ],
                                      "product_id" => "string",
                                    ],
                                  ],
                                ],
                              ],
                            ],
                          ])
                        ->create();

        $job = new SupplierPull($supplier);

        $job->handle();

        $this->assertDatabaseCount('products', 13002);
    }


}
