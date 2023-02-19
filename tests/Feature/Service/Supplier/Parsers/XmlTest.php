<?php

namespace Tests\Feature\Service\Supplier\Parsers;

use App\Models\Supplier;
use App\Services\Supplier\Parsers\XmlParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tests\Traits\CopyToImportDisk;

class XmlTest extends TestCase
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

        $parser = new XmlParser($supplier);

        $result = $parser->parse($this->copyToImport($supplier->uri));

        $this->assertEquals(
        [
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
            ],
        ], $result);
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

        $parser = new XmlParser($supplier);

        $result = $parser->parse($this->copyToImport('supplier_import_simple_non_root.xml'));

        $this->assertEquals([
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
                ],
              ], $result);
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

        $parser = new XmlParser($supplier);

        $result = $parser->parse($this->copyToImport('supplier_import_nested.xml'));

        $this->assertEquals(
        [
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
            ],
        ], $result);

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

        $parser = new XmlParser($supplier);

        $result = $parser->parse($this->copyToImport('supplier_import_nested_with_attributes.xml'));

        $this->assertEquals(
        [
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
            ],
        ], $result);

    }

}
