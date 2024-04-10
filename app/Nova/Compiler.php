<?php

namespace App\Nova;

use App\Nova\Actions\CompileProducts;
use App\Nova\Actions\CompileProductsFull;
use App\Nova\Metrics\CompiledProducts;
use App\Nova\Metrics\ProductsBySource;
use App\Nova\Metrics\StaleProductsByProcessor;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\KeyValue;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;

class Compiler extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\Compiler::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'name'
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
        return [
            ID::make(__('ID'), 'id')->sortable(),
            Text::make(__('Name'), 'name')->sortable()->rules('required'),
            KeyValue::make(__('Fields'), 'fields')->default([
                'ean' => 'string'
            ])->keyLabel(__('Field'))->valueLabel(__('Type')),
            Text::make(__('Rules'), 'rules')->hideFromIndex(),
            Number::make(__('Compilation interval'), 'interval')
                ->hideFromIndex()
                ->min(0)
                ->max(86400)
                ->step(1)
                ->help(__('Time in seconds. 0 = inactive.'))
                ->rules('required'),
            HasMany::make(__('Processors')),
            HasMany::make(__('Exports'), 'exports'),
            HasMany::make(__('Compiled products'), 'compiledProducts'),
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
        return [
            (new CompiledProducts)->onlyOnDetail(),
            (new ProductsBySource)->onlyOnDetail(),
            (new StaleProductsByProcessor)->onlyOnDetail(),
        ];
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
            CompileProducts::make(),
            CompileProductsFull::make(),
        ];
    }
}
