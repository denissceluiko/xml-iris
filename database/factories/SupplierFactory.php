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
        ];
    }

    public function uri(string $uri) : self
    {
        return $this->state(function (array $attributes) use ($uri) {
            return ['uri' => $uri];
        });
    }

    public function config(array $config) : self
    {
        return $this->state(function (array $attributes) use ($config) {
            return ['config' => $config];
        });
    }

    public function structure(array $structure) : self
    {
        return $this->state(function (array $attributes) use ($structure) {
            return ['structure' => $structure];
        });
    }

}
