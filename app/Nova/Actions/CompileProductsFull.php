<?php

namespace App\Nova\Actions;

use App\Jobs\Compiler\CompileJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;

class CompileProductsFull extends Action
{
    use InteractsWithQueue, Queueable;

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

        foreach ($models as $model) {
            $batch[] = new CompileJob($model, true);
        }

        Bus::batch($batch)
            ->name('Manual compilation jobs (full)')
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

    public function name()
    {
        return __('Compile products (full)');
    }
}
