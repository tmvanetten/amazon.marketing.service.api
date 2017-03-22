<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        'App\Console\Commands\AmazonDownload',
        'App\Console\Commands\RequestApiReports',
        'App\Console\Commands\GetReportToDatabase'
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('amazon:request_report')
            ->dailyAt('01:00')->appendOutputTo(storage_path('requestLog.txt'));
        $schedule->command('amazon:get_report_to_database')
            ->dailyAt('01:20')->appendOutputTo(storage_path('saveReportToDBLog.txt'));
    }
}
