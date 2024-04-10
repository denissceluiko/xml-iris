<?php

namespace App\Nova\Metrics;

use Illuminate\Support\Facades\DB;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;

class StaleProductsByProcessor extends Partition
{
    /**
     * Calculate the value of the metric.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return mixed
     */
    public function calculate(NovaRequest $request)
    {
        $results = DB::table('processed_products')
            ->selectRaw('processor_id, count(*) as stale_count')
            ->where('stale_level', '>', '0')
            ->whereIn('processor_id',
                DB::table('processors')->select('id')->where('compiler_id', $request->resourceId)
            )
            ->groupBy('processor_id')
            ->get();

        return $this->result($results->pluck('stale_count', 'processor_id')->toArray());
    }

    /**
     * Determine for how many minutes the metric should be cached.
     *
     * @return  \DateTimeInterface|\DateInterval|float|int
     */
    public function cacheFor()
    {
        return now()->addMinutes(5);
    }


    public function name() : string
    {
        return __('Stale products by processor');
    }

    /**
     * Get the URI key for the metric.
     *
     * @return string
     */
    public function uriKey()
    {
        return 'stale-products-by-processor';
    }
}
