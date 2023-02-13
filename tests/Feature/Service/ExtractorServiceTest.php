<?php

namespace Tests\Feature\Service;

use App\Services\Processor\ExtractorService;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
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
            'price' => 'cena->where([waluta="EUR"])->[wartosc]',
        ]);

        $extracted = $extractor->extract([
            "name" => "{}product",
            "value" => [
                [
                    "name" => "{}cena",
                    "value" => [
                      [
                        "name" => "{}cena_netto",
                        "value" => null,
                        "attributes" => [
                          "waluta" => "USD",
                          "wartosc" => "1.04",
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
            'price' => '0.94',
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
