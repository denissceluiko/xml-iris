<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Supplier>
 */
class SupplierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => fake()->words(2, true),
            'uri' => storage_path('app/public/sample_supplier.xml'),
            'config' => [
                'xmlns' => '',
                'root_tag' => 'produkty',
                'product_tag' => 'produkt',
            ],
            'structure' => [
                "ean" => "string",
                "cena" => [
                  "type" => "repeatingElements",
                  "child" => "cena_netto",
                ],
                "nazwy" => [
                  "type" => "repeatingElements",
                  "child" => "nazwa",
                ],
                "opisy" => [
                  "type" => "repeatingElements",
                  "child" => "opis",
                ],
                "produkt" => [
                  "type" => "keyValue",
                ],
                "zdjecia" => [
                  "type" => "repeatingElements",
                  "child" => "zdjecie",
                ],
                "produkty" => [
                  "type" => "repeatingElements",
                  "child" => "produkt",
                ],
                "kategoria" => "string",
                "producent" => "string",
                "dostepnosc" => "int",
                "kategoria_tree" => "string",
              ],
        ];
    }
}
