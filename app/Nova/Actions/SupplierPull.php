<?php

namespace App\Nova\Actions;

use App\Jobs\SupplierPull as JobsSupplierPull;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Actions\Actionable;
use Laravel\Nova\Fields\ActionFields;

class SupplierPull extends Action
{
    use Actionable, InteractsWithQueue, Queueable;

    /**
     * Perform the action on the given models.
     *
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     * @param  \Illuminate\Support\Collection  $models
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        $batch = [];

        foreach ($models as $supplier) {
            $batch[] = new JobsSupplierPull($supplier);
        }

        Bus::batch($batch)
            ->name('Manual Supplier pull')
            ->onQueue('long-running-queue')
            ->dispatch();
    }

    /**
     * Get the fields available on the action.
     *
     * @return array
     */
    public function fields()
    {
        return [];
    }
}
