<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SampleSupplierData extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Supplier::factory()
            ->uri('sample_supplier.xml')
            ->config([
                "xmlns" => "",
                "root_tag" => "produkty",
                "product_tag" => "produkt",
                "source_type" => "xml",
              ])
            ->structure([
                "produkty" => [
                  "type" => "repeatingElements",
                  "child" => "produkt",
                  "value" => [
                    "type" => "keyValue",
                    "value" => [
                      "ean" => "ean",
                      "cena" => [
                        "type" => "repeatingElements",
                        "child" => "cena_netto",
                        "value" => "",
                        "attributes" => [
                          "waluta" => "currency",
                          "wartosc" => "float",
                        ],
                      ],
                      "nazwy" => [
                        "type" => "repeatingElements",
                        "child" => "nazwa",
                        "attributes" => [
                          "jezik" => "languageCode",
                        ],
                      ],
                      "opisy" => [
                        "type" => "repeatingElements",
                        "child" => "opis",
                        "attributes" => [
                          "jezik" => "languageCode",
                        ],
                      ],
                      "produkt" => [
                        "type" => "keyValue",
                        "attributes" => [
                          "id" => "int",
                        ],
                      ],
                      "zdjecia" => [
                        "type" => "repeatingElements",
                        "child" => "zdjecie",
                        "attributes" => [
                          "id" => "int",
                          "url" => "url",
                          "glowne" => "int",
                        ],
                      ],
                      "kategoria" => "string",
                      "producent" => "string",
                      "dostepnosc" => "int",
                      "kategoria_tree" => "string",
                    ],
                    "attributes" => [
                      "id" => "int",
                    ],
                  ],
                ],
              ])
            ->create();

            Supplier::factory()
            ->uri('sample_supplier_big.xml')
            ->config([
                "xmlns" => "",
                "root_tag" => "produkty",
                "product_tag" => "produkt",
                "source_type" => "xml",
              ])
            ->structure([
                "produkty" => [
                  "type" => "repeatingElements",
                  "child" => "produkt",
                  "value" => [
                    "type" => "keyValue",
                    "value" => [
                      "ean" => "ean",
                      "cena" => [
                        "type" => "repeatingElements",
                        "child" => "cena_netto",
                        "value" => "",
                        "attributes" => [
                          "waluta" => "currency",
                          "wartosc" => "float",
                        ],
                      ],
                      "nazwy" => [
                        "type" => "repeatingElements",
                        "child" => "nazwa",
                        "attributes" => [
                          "jezik" => "languageCode",
                        ],
                      ],
                      "opisy" => [
                        "type" => "repeatingElements",
                        "child" => "opis",
                        "attributes" => [
                          "jezik" => "languageCode",
                        ],
                      ],
                      "produkt" => [
                        "type" => "keyValue",
                        "attributes" => [
                          "id" => "int",
                        ],
                      ],
                      "zdjecia" => [
                        "type" => "repeatingElements",
                        "child" => "zdjecie",
                        "attributes" => [
                          "id" => "int",
                          "url" => "url",
                          "glowne" => "int",
                        ],
                      ],
                      "kategoria" => "string",
                      "producent" => "string",
                      "dostepnosc" => "int",
                      "kategoria_tree" => "string",
                    ],
                    "attributes" => [
                      "id" => "int",
                    ],
                  ],
                ],
              ])
            ->create();
    }
}
