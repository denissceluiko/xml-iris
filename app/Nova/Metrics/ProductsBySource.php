<?php

namespace App\Nova\Metrics;

use App\Models\Compiler;
use App\Models\Processor;
use Illuminate\Support\Facades\DB;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;

class ProductsBySource extends Partition
{
    /**
     * Calculate the value of the metric.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return mixed
     */
    public function calculate(NovaRequest $request)
    {
        $result = DB::table('processors')
            ->selectRaw('suppliers.name as name, count(*) as product_count')
            ->join('suppliers', 'suppliers.id', 'processors.supplier_id')
            ->join('processed_products', 'processed_products.processor_id', 'processors.id')
            ->where('processors.compiler_id', $request->resourceId)
            ->groupBy('processors.id')
            ->get();

        return $this->result($result->pluck('product_count', 'name')->toArray());
    }

    public function name() : string
    {
        return __('Products by source');
    }

    /**
     * Determine for how many minutes the metric should be cached.
     *
     * @return  \DateTimeInterface|\DateInterval|float|int
     */
    public function cacheFor()
    {
        return now()->addMinutes(60);
    }

    /**
     * Get the URI key for the metric.
     *
     * @return string
     */
    public function uriKey()
    {
        return 'products-by-source';
    }
}
