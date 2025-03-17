<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('fetch:emails')->timezone('America/New_York')
            ->weekdays() // Run only on weekdays (Mon-Fri)
            ->at('12:00') // Run at 12 PM
            ->at('14:00') // Run at 2 PM
            ->at('16:00'); // Run at 4 PM

        $schedule->command('fetch:emailsForSecondSheet')->timezone('America/New_York')
            ->weekdays() // Run only on weekdays (Mon-Fri)
            ->at('12:00') // Run at 12 PM
            ->at('14:00') // Run at 2 PM
            ->at('16:00'); // Run at 4 PM

    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}