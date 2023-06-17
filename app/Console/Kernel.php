<?php

namespace App\Console;

use App\Jobs\ExportCycle;
use App\Jobs\Maintenance\PurgeAbandonedProducts;
use App\Jobs\UpdateCycle;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->job(new UpdateCycle)
                ->everyTenMinutes()
                ->withoutOverlapping();

        $schedule->job(new ExportCycle)
                ->everyTenMinutes()
                ->withoutOverlapping();

        $schedule->job(new PurgeAbandonedProducts)
                ->everyThreeHours();

        $schedule->command('horizon:snapshot')->everyFiveMinutes();
        $schedule->command('queue:prune-batches --hours=12 --unfinished=168 --cancelled=168')->daily();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
