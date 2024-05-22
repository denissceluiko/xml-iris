<?php

namespace Tests\Feature\Service;

use App\Services\Processor\ExtractorService;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ExtractorServiceTest extends TestCase
{
    /**
     * @test
     * @return void
     */
    public function can_extract_shallow_values()
    {
        $extractor = new ExtractorService([
            'ean' => 'ean',
            'sku' => '[id]',
            'eantype' => 'ean->[type]',
        ]);

        $extracted = $extractor->extract([
            "name" => "{}product",
            "value" => [
              [
                "name" => "{}ean",
                "value" => "1010110101010",
                "attributes" => [
                    "type" => "ean13"
                ],
              ],
            ],
            "attributes" => [
              "id" => "101010",
            ],
        ]);


        $this->assertEquals([
            'ean' => '1010110101010',
            'sku' => '101010',
            'eantype' => 'ean13'
        ], $extracted);
    }

    /**
     * @test
     * @return void
     */
    public function can_handle_empty_values()
    {
        $extractor = new ExtractorService([
            'ean' => 'ean',
            'sku' => '[id]',
            'eantype' => '',
        ]);

        $extracted = $extractor->extract([
            "name" => "{}product",
            "value" => [
              [
                "name" => "{}ean",
                "value" => "1010110101010",
                "attributes" => [
                    "type" => "ean13"
                ],
              ],
            ],
            "attributes" => [
              "id" => "101010",
            ],
        ]);


        $this->assertEquals([
            'ean' => '1010110101010',
            'sku' => '101010',
            'eantype' => ''
        ], $extracted);
    }

    /**
     * @test
     * @return void
     */
    public function can_fail_lookup_gracefully()
    {
        $extractor = new ExtractorService([
            'ean' => '[id]',
            'usd' => 'price->where(null)->value', // Empty where
            'jpy' => 'price->where([currency="JPY"])->[value]', // Non-existent where() result
            'eur' => 'price->where([currency="EUR"])->[no_attribute]', // Non-existent atribute after where()
            'sum' => 'price->where([currency="EUR"])->[value]', // Valid lookup
        ]);

        $extracted = $extractor->extract([
            "name" => "{}product",
            "value" => [
                [
                    "name" => "{}price",
                    "value" => [
                      [
                        "name" => "{}price_netto",
                        "value" => null,
                        "attributes" => [
                          "currency" => "EUR",
                          "value" => "1.04",
                        ],
                      ],
                    ],
                    "attributes" => [],
                ],
            ],
            "attributes" => [
              "id" => "101010",
            ],
        ]);


        $this->assertEquals([
            'ean' => '101010',
            'jpy' => null,
            'eur' => null,
            'usd' => null,
            'sum' => 1.04,
        ], $extracted);
    }

    /**
     * @test
     * @return void
     */
    public function can_extract_deeper_values()
    {
        $extractor = new ExtractorService([
            'language' => 'level1->level2->[language]',
            'name' => 'level1->level2->level3'
        ]);

        $extracted = $extractor->extract([
            "name" => "{}product",
            "value" => [
              [
                "name" => "{}ean",
                "value" => "1010110101010",
                "attributes" => [
                    "type" => "ean13"
                ],
              ],
              [
                "name" => "{}level1",
                "value" => [
                  [
                    "name" => "{}level2",
                    "value" => [
                        [
                          "name" => "{}level3",
                          "value" => "Amazing product name",
                          "attributes" => [],
                        ],
                    ],
                    "attributes" => [
                      "language" => "en",
                    ],
                  ],
                ],
                "attributes" => [],
              ],
            ],
            "attributes" => [
              "id" => "101010",
            ],
        ]);


        $this->assertEquals([
            'language' => 'en',
            'name' => 'Amazing product name',
        ], $extracted);
    }


    /**
     * @test
     * @return boolean
     */
    public function can_handle_queries()
    {
        $extractor = new ExtractorService([
            'sku' => '[id]',
            'price_1' => 'cena->where([waluta="EUR"])->[wartosc]',
            'price_2' => 'cena->where("cena_netto")->where([waluta="USD"])->[wartosc]',
            'price_3' => 'cena->where([waluta="EUR"])->where("cena_netto")->[wartosc]',
        ]);

        $extracted = $extractor->extract([
            "name" => "{}product",
            "value" => [
                [
                    "name" => "{}cena",
                    "value" => [
                      [
                        "name" => "{}cena_brutto",
                        "value" => null,
                        "attributes" => [
                          "waluta" => "EUR",
                          "wartosc" => "0.96",
                        ],
                      ],
                      [
                        "name" => "{}cena_netto",
                        "value" => null,
                        "attributes" => [
                          "waluta" => "EUR",
                          "wartosc" => "0.94",
                        ],
                      ],
                      [
                        "name" => "{}cena_netto",
                        "value" => null,
                        "attributes" => [
                          "waluta" => "USD",
                          "wartosc" => "1.04",
                        ],
                      ],
                    ],
                    "attributes" => [],
                ],
            ],
            "attributes" => [
              "id" => "101010",
            ],
        ]);


        $this->assertEquals([
            'sku' => '101010',
            'price_1' => '0.96',
            'price_2' => '1.04',
            'price_3' => '0.94',
        ], $extracted);
    }

    /**
     * @test
     * @return boolean
     */
    public function can_handle_non_existent_values()
    {
        $extractor = new ExtractorService([
            'ean' => 'ean',
            'sku' => '[id]',
            'eantype' => 'int',
        ]);

        $extracted = $extractor->extract([
            "name" => "{}product",
            "value" => [
              [
                "name" => "{}ean",
                "value" => "1010110101010",
                "attributes" => [
                    "type" => "ean13"
                ],
              ],
            ],
            "attributes" => [
              "id" => "101010",
            ],
        ]);


        $this->assertEquals([
            'ean' => '1010110101010',
            'sku' => '101010',
            'eantype' => null
        ], $extracted);
    }
}
