<?php

namespace Tests\Feature\Jobs\Supplier;

use App\Jobs\Product\UpsertJob;
use App\Jobs\XmlSupplierParseJob;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class XmlSupplierParserJobTest extends TestCase
{
    use RefreshDatabase;

    public function setUp() : void
    {
        parent::setUp();

        config()->set('filesystems.disks.local.root', base_path('tests/data'));
    }

    /**
     * A basic supplier parse test.
     *
     * @test
     * @return void
     */
    public function can_parse_simple_supplier()
    {

        $supplier = Supplier::factory()
                        ->uri('supplier_import_simple.xml')
                        ->config([
                            'root_tag' => 'products',
                            'product_tag' => 'product',
                            'source_type' => 'xml',
                            'ean_path' => 'ean',
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

        $expected =
        [
            "name" => "{}product",
            "value" => [
            [
                "name" => "{}ean",
                "value" => "0000000000000",
                "attributes" => [],
            ],
            [
                "name" => "{}name",
                "value" => "Sample product",
                "attributes" => [],
            ],
            [
                "name" => "{}stock",
                "value" => "99",
                "attributes" => [],
            ],
            [
                "name" => "{}currency",
                "value" => "EUR",
                "attributes" => [],
            ],
            [
                "name" => "{}price",
                "value" => "10.00",
                "attributes" => [],
            ],
            ],
            "attributes" => [],
        ];

        Bus::fake();

        $parser = new XmlSupplierParseJob($supplier, Storage::path($supplier->uri));

        $parser->handle();

        Bus::assertDispatched(function (UpsertJob $job) use ($expected) {
            return $job->ean === "0000000000000" && $job->values = $expected;
        });

    }

    /**
     * @test
     *
     * @return void
     */
    public function can_parse_simple_supplier_non_root()
    {
        $supplier = Supplier::factory()
                        ->config([
                            'root_tag' => 'products',
                            'product_tag' => 'product',
                            'source_type' => 'xml',
                            'ean_path' => 'ean',
                        ])
                        ->structure([
                            "supplier" => [
                                "type" => "keyValue",
                                "value" => [
                                    "title" => "string",
                                    "link" => "url",
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
                                ]
                            ]
                        ])
                        ->create();

        $expected = [
            "name" => "{}product",
            "value" => [
                [
                "name" => "{}ean",
                "value" => "0000000000000",
                "attributes" => [],
                ],
                [
                "name" => "{}name",
                "value" => "Sample product",
                "attributes" => [],
                ],
                [
                "name" => "{}stock",
                "value" => "99",
                "attributes" => [],
                ],
                [
                "name" => "{}currency",
                "value" => "EUR",
                "attributes" => [],
                ],
                [
                "name" => "{}price",
                "value" => "10.00",
                "attributes" => [],
                ],
            ],
            "attributes" => [],
        ];

        Bus::fake();

        $parser = new XmlSupplierParseJob($supplier, Storage::path('supplier_import_simple_non_root.xml'));

        $parser->handle();

        Bus::assertDispatched(function (UpsertJob $job) use ($expected) {
            return $job->ean === "0000000000000" && $job->values = $expected;
        });
    }

    /**
     * Test handling of nested keyValue and repeatingElements.
     *
     * @test
     * @return void
     */
    public function can_parse_simple_supplier_with_nested_elements()
    {
        $supplier = Supplier::factory()
                        ->config([
                            'root_tag' => 'products',
                            'product_tag' => 'product',
                            'source_type' => 'xml',
                            'ean_path' => 'ean',
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
                                            "price_data" => [
                                                "type" => "keyValue",
                                                "value" => [
                                                    "currency" => "currency",
                                                    "price" => "float",
                                                ],
                                            ],
                                            "images" => [
                                                "type" => "repeatingElements",
                                                "child" => "image",
                                                "value" => "uri",
                                                "attributes" => [],
                                            ],
                                        ]
                                    ]
                                ]
                            ]
                        ])
                        ->create();

        $expected = [
            "name" => "{}product",
            "value" => [
              [
                "name" => "{}ean",
                "value" => "0000000000000",
                "attributes" => [],
              ],
              [
                "name" => "{}name",
                "value" => "Sample product",
                "attributes" => [],
              ],
              [
                "name" => "{}stock",
                "value" => "99",
                "attributes" => [],
              ],
              [
                  "name" => "{}price_data",
                  "value" => [
                      [
                          "name" => "{}currency",
                          "value" => "EUR",
                          "attributes" => [],
                      ],
                      [
                          "name" => "{}price",
                          "value" => "10.00",
                          "attributes" => [],
                      ],
                  ],
                  "attributes" => [],
              ],
              [
                  "name" => "{}images",
                  "value" => [
                      [
                          "name" => "{}image",
                          "value" => "https://example.com/images/1.png",
                          "attributes" => [],
                      ],
                      [
                          "name" => "{}image",
                          "value" => "https://example.com/images/2.png",
                          "attributes" => [],
                      ],
                      [
                          "name" => "{}image",
                          "value" => "https://example.com/images/3.png",
                          "attributes" => [],
                      ],
                  ],
                  "attributes" => [],
              ],
            ],
            "attributes" => [],
        ];

        Bus::fake();

        $parser = new XmlSupplierParseJob($supplier, Storage::path('supplier_import_nested.xml'));

        $parser->handle();

        Bus::assertDispatched(function (UpsertJob $job) use ($expected) {
            return $job->ean === "0000000000000" && $job->values = $expected;
        });
    }


    /**
     * Test handling of nested keyValue and repeatingElements with attributes.
     *
     * @test
     * @return void
     */
    public function can_parse_simple_supplier_with_nested_elements_and_attributes()
    {
        $supplier = Supplier::factory()
                        ->config([
                            'root_tag' => 'products',
                            'product_tag' => 'product',
                            'source_type' => 'xml',
                            'ean_path' => 'ean',
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
                                            "name" => [
                                                "type" => "string",
                                                "attributes" => [
                                                    "lang" => "languageCode"
                                                ],
                                            ],
                                            "stock" => "integer",
                                            "currency" => "currencyCode",
                                            "price_data" => [
                                                "type" => "keyValue",
                                                "value" => [
                                                    "currency" => [
                                                        "type" => "currency",
                                                        "attributes" => [
                                                            "code" => "currency"
                                                        ],
                                                    ],
                                                    "price" => "float",
                                                ],
                                            ],
                                            "images" => [
                                                "type" => "repeatingElements",
                                                "child" => "image",
                                                "value" => "uri",
                                                "attributes" => [
                                                    "src" => "url",
                                                ],
                                            ],
                                        ]
                                    ]
                                ]
                            ]
                        ])
                        ->create();

        $expected = [
            "name" => "{}product",
            "value" => [
              [
                "name" => "{}ean",
                "value" => "0000000000000",
                "attributes" => [],
              ],
              [
                "name" => "{}name",
                "value" => "Sample product",
                "attributes" => [
                  "lang" => "en"
                ],
              ],
              [
                "name" => "{}stock",
                "value" => "99",
                "attributes" => [],
              ],
              [
                  "name" => "{}price_data",
                  "value" => [
                      [
                          "name" => "{}currency",
                          "value" => "€",
                          "attributes" => [
                              "code" => "EUR"
                          ],
                      ],
                      [
                          "name" => "{}price",
                          "value" => "10.00",
                          "attributes" => [],
                      ],
                  ],
                  "attributes" => [],
              ],
              [
                  "name" => "{}images",
                  "value" => [
                      [
                          "name" => "{}image",
                          "value" => null,
                          "attributes" => [
                              "src" => "https://example.com/images/1.png"
                          ],
                      ],
                      [
                          "name" => "{}image",
                          "value" => null,
                          "attributes" => [
                              "src" => "https://example.com/images/2.png"
                          ],
                      ],
                      [
                          "name" => "{}image",
                          "value" => null,
                          "attributes" => [
                              "src" => "https://example.com/images/3.png"
                          ],
                      ],
                  ],
                  "attributes" => [],
              ],
            ],
            "attributes" => [],
        ];

        Bus::fake();

        $parser = new XmlSupplierParseJob($supplier, Storage::path('supplier_import_nested_with_attributes.xml'));

        $parser->handle();

        Bus::assertDispatched(function (UpsertJob $job) use ($expected) {
            return $job->ean === "0000000000000" && $job->values = $expected;
        });

    }

    /**
     * A basic ean extractor test.
     *
     * @test
     * @return void
     */
    public function can_extract_ean_simple()
    {

        $supplier = Supplier::factory()
                        ->uri('supplier_ean_simple.xml')
                        ->config([
                            'root_tag' => 'products',
                            'product_tag' => 'product',
                            'source_type' => 'xml',
                            'ean_path' => 'ean',
                        ])
                        ->structure([
                            "products" => [
                                "type" => "repeatingElements",
                                "child" => "product",
                                "value" => [
                                    "product" => [
                                        "type" => "keyValue",
                                        "value" => [
                                            "ean" => "ean",
                                        ]
                                    ]
                                ]
                            ]
                        ])
                        ->create();

        Bus::fake();

        $parser = new XmlSupplierParseJob($supplier, Storage::path($supplier->uri));
        $parser->handle();

        Bus::assertDispatched(function (UpsertJob $job) {
            return $job->ean === "0000000000000";
        });
    }

    /**
     * A basic ean extractor test.
     *
     * @test
     * @return void
     */
    public function can_extract_ean_nested()
    {

        $supplier = Supplier::factory()
                        ->uri('supplier_ean_nested.xml')
                        ->config([
                            'root_tag' => 'products',
                            'product_tag' => 'product',
                            'source_type' => 'xml',
                            'ean_path' => 'properties->ean',
                        ])
                        ->structure([
                            "products" => [
                                "type" => "repeatingElements",
                                "child" => "product",
                                "value" => [
                                    "product" => [
                                        "type" => "keyValue",
                                        "value" => [
                                            "properties" => [
                                                "type" => "keyValue",
                                                "value" => [
                                                    "main" => "string",
                                                ],
                                            ],
                                        ]
                                    ]
                                ]
                            ]
                        ])
                        ->create();

        Bus::fake();

        $parser = new XmlSupplierParseJob($supplier, Storage::path($supplier->uri));
        $parser->handle();

        Bus::assertDispatched(function (UpsertJob $job) {
            return $job->ean === "0000000000000";
        });
    }

    /**
     * A basic ean extractor test.
     *
     * @test
     * @return void
     */
    public function can_extract_ean_as_attribute()
    {

        $supplier = Supplier::factory()
                        ->uri('supplier_ean_as_attribute.xml')
                        ->config([
                            'root_tag' => 'products',
                            'xmlns' => 'iaiext',
                            'product_tag' => 'product',
                            'source_type' => 'xml',
                            'ean_path' => 'sizes->size->[producer_code]',
                        ])
                        ->structure([
                            "products" => [
                                "type" => "repeatingElements",
                                "child" => "product",
                                "value" => [
                                    "product" => [
                                        "type" => "keyValue",
                                        "value" => [
                                            "sizes" => [
                                                "type" => "repeatingElements",
                                                "child" => "size",
                                                "attributes" => [
                                                    "producer_code" => "ean",
                                                ],
                                            ],
                                        ]
                                    ]
                                ]
                            ]
                        ])
                        ->create();

        Bus::fake();

        $parser = new XmlSupplierParseJob($supplier, Storage::path($supplier->uri));
        $parser->handle();

        Bus::assertDispatched(function (UpsertJob $job) {
            return $job->ean === "0000000000000";
        });
    }
}
