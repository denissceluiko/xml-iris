<?php

return [
    'one' => [
        'rules' => [
            'ean' => 'ean',
            'sku' => 'ean',
            'stock' => 'dostepnosc',
            'price_lv' => 'cena->where([waluta="EUR"])->[wartosc]',
            'price_ee' => 'float',
        ],
        'product_json' => '{
            "name": "{}produkt",
            "value": [
                {
                    "name": "{}cena",
                    "value": [
                        {
                            "name": "{}cena_netto",
                            "value": null,
                            "attributes": {
                                "waluta": "EUR",
                                "wartosc": "0.85"
                            }
                        }
                    ],
                    "attributes": []
                },
                {
                    "name": "{}ean",
                    "value": "5903396067990",
                    "attributes": []
                },
                {
                    "name": "{}dostepnosc",
                    "value": "0",
                    "attributes": []
                }
            ],
            "attributes": {
                "id": "432728"
            }
        }',
        'expected' => [
            'ean' => '5903396067990',
            'sku' => '5903396067990',
            'stock' => '0',
            'price_lv' => '0.85',
            'price_ee' => null,
        ],
    ],
    'two' => [
        'rules' => [
            'ean' => 'ean',
            'sku' => 'ean',
            'stock' => 'dostepnosc',
            'price_lv' => 'cena->where([waluta="EUR"])->[wartosc]',
            'price_ee' => 'float',
        ],
        'product_json' => '{
            "name": "{}produkt",
            "value": [
                {
                    "name": "{}cena",
                    "value": null,
                    "attributes": []
                },
                {
                    "name": "{}ean",
                    "value": "5903396067990",
                    "attributes": []
                },
                {
                    "name": "{}dostepnosc",
                    "value": "0",
                    "attributes": []
                }
            ],
            "attributes": {
                "id": "432728"
            }
        }',
        'expected' => [
            'ean' => '5903396067990',
            'sku' => '5903396067990',
            'stock' => '0',
            'price_lv' => null,
            'price_ee' => null,
        ],
    ],
];

