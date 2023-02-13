<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'ean' => fake()->ean13(),
        ];
    }

    public function supplier($supplier)
    {
        return $this->state(function (array $attributes) use ($supplier) {
            return ['supplier_id' => is_int($supplier) ? $supplier : $supplier->id];
        });
    }

    /**
     * Populates 'value' field based on rules (preferably) from Supplier's structure and configuration array.
     * @param array $rule
     * @param array $except
     * @return self
     */
    public function generateValues(array $rules, array $except = []) : self
    {
        return $this->state(function(array $attributes) use ($rules, $except) {
            $fields = [];

            foreach ($rules as $key => $rule) {
                if (array_key_exists($key, $except)) continue;

                $fields[$key] = $this->generateField($key, $rule);
            }

            return ['values' => $fields];
        });
    }

    /**
     * Generates a singular field from given rules.
     *
     * @param string $name
     * @param mixed $rule
     * @param array $attributes
     * @return array
     */
    protected function generateField(string $name, mixed $rule, $attributes = []) : array
    {
        if (!is_array($rule)) {
            $rule = [
                'value' => $rule,
            ];
        }

        if (is_array($rule['value'])) {
            return $this->resolveArrayRule($rule['value']);
        }

        $value = $this->generateSimpleType($rule['value'] ?? '');
        $attributes = $this->generateAttributes($rule['attributes'] ?? []);

        return $this->formatField($name, $value, $attributes);
    }

    /**
     * Resolves nested rules
     *
     * @param array $rule
     * @return array
     */
    protected function resolveArrayRule(array $rule) : array
    {
        $fields = [];

        if ($rule['type'] == "keyValue" && isset($rule['value'])) {
            foreach ($rule['fields'] as $key => $field) {
                $fields[$key] = $this->generateField($key, $field['value'], $field['attributes']);
            }
        } else if ($rule['type'] == "repeatingElements" && isset($rule['value'])) {
            foreach($rule['value'] as $key => $element) {
                $fields[] = $this->generateField('', $element['value'], $element['attributes']);
            }
        }

        return $fields;
    }

    protected function generateSimpleType(string $type = '')
    {
        if ($type == '') return null;

        $value = null;

        switch ($type) {
            case 'string':
                $value = fake()->words(2, true);
                break;
            case 'url':
                $value = fake()->url();
                break;
            case 'ean':
                $value = fake()->ean13();
                break;
            case 'int':
                $value = fake()->randomNumber(4);
                break;
            case 'float':
                $value = fake()->randomFloat(2);
                break;
            case 'currency':
                $value = fake()->currencyCode();
                break;
            case 'languageCode':
                $value = fake()->languageCode();
                break;
            default:
                break;
        }

        return $value;
    }

    protected function generateAttributes(array $rules = []) : array
    {
        $attributes = [];

        foreach ($rules as $key => $type) {
            $attributes[$key] = $this->generateSimpleType($type);
        }

        return [$attributes];
    }

    protected function formatField($name, $value = '', $attributes = []) : array
    {
        return [
            'name' => '{}'.$name,
            'value' => $value,
            'attributes' => $attributes,
        ];
    }
}
