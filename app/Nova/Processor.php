<?php

namespace App\Nova;

use App\Nova\Actions\ProcessProducts;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\KeyValue;

class Processor extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\Processor::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'id';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id'
    ];

    public static $displayInNavigation = false;

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
        return [
            ID::make(),
            BelongsTo::make('Compiler'),
            BelongsTo::make('Supplier'),
            KeyValue::make(__('Mappings'), 'mappings')
                ->keyLabel(__('Field'))
                ->valueLabel(__('Product value path')),
            KeyValue::make(__('Transformations'), 'transformations')
                ->keyLabel(__('Field'))
                ->valueLabel(__('Transformation')),
            HasMany::make(__('Processed products'), 'processedProducts'),
        ];
    }


    public function fieldsForCreate(Request $request)
    {
        return [
            BelongsTo::make('Compiler'),
            BelongsTo::make('Supplier'),
        ];
    }


    public function fieldsForUpdate(Request $request)
    {
        return [
            KeyValue::make(__('Mappings'), 'mappings', function() {
                return $this->fillMappings();
            })
            ->keyLabel(__('Field'))
            ->valueLabel(__('Product value path')),
        KeyValue::make(__('Transformations'), 'transformations', function() {
                return $this->fillTransformations();
            })
            ->keyLabel(__('Field'))
            ->valueLabel(__('Transformation')),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function cards(Request $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function filters(Request $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function lenses(Request $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function actions(Request $request)
    {
        return [
            ProcessProducts::make(),
        ];
    }

    public function fillMappings() : array
    {
        return $this->generateFields($this->compiler->fields, $this->mappings);
    }

    public function fillTransformations() : array
    {
        $keys = array_keys($this->compiler->fields);
        $defaultFields = array_combine($keys, $keys);
        return $this->generateFields($defaultFields, $this->transformations);
    }

    public function generateFields($master, $current)
    {
        $diff = array_diff_key($master, $current ?? []);

        foreach ($diff as $key => $value) {
            $current[$key] = $value;
        }
        return $current;
    }
}
