<?php
/**
 * Created by PhpStorm.
 * User: Anatolii
 * Date: 3/14/2017
 * Time: 11:49 AM
 */

namespace App\Providers;

use Laravel\Lumen\Providers\EventServiceProvider as ServiceProvider;

class RequestApiReportServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        foreach (glob(base_path('app').'/Helpers/*.php') as $filename){
            require_once($filename);
        }
    }
}