<?php

namespace Database\Factories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Compiler>
 */
class CompilerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => fake()->words(3, true),
            'rules' => '',
            'fields' => function ($attributes) {
                return $this->getFields($attributes);
            },
            'last_compiled_at' => null,
            'interval' => 0,
        ];

    }

    public function name(string $name) : self
    {
        return $this->state(function($attributes) use ($name) {
            return ['name' => $name];
        });
    }

    public function rules(string $rules) : self
    {
        return $this->state(function($attributes) use ($rules) {
            return ['rules' => $rules];
        });
    }

    public function compiledAt(Carbon $time = null) : self
    {
        $time = $time ?? Carbon::now();

        return $this->state(function (array $attributes) use ($time) {
            return ['last_compiled_at' => $time];
        });
    }

    public function interval(int $interval) : self
    {
        return $this->state(function (array $attributes) use ($interval) {
            return ['interval' => $interval];
        });
    }


    public function fields(array $fields) : self
    {
        return $this->state(function($attributes) use ($fields) {
            return ['fields' => $fields];
        });
    }

    public function makeFields(int $count) : self
    {
        return $this->state(function($attributes) use ($count) {
            return ['fields' => $this->generateFields($count)];
        });
    }

    protected function generateFields(int $count) : array
    {
        $fields = [];

        while ($count > 1) {
            $fields[fake()->word()] = fake()->randomElement(['string', 'int', 'float']);
            $count--;
        }

        return $fields;
    }

    protected function getFields($attributes) : array
    {
        return is_callable($attributes['fields']) ? [] : $attributes['fields'];
    }

}
