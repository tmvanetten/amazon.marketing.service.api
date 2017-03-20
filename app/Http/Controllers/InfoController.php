<?php

namespace App\Http\Controllers;

use App\RequestReportAPI;

class InfoController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Retrieve  last seven days date from server.
     *
     * @return json response
     */
    public function getDates() {
        $dates = [
            array('value' =>date("Y-m-d", time() - 60 * 60 * 24), 'label' => 'Yesterday' ),
            array('value' => date("Y-m-d", time() - 60 * 60 * 48), 'label' => 'Past 2 Days' ),
            array('value' => date("Y-m-d", time() - 60 * 60 * 72), 'label' => 'Past 3 Days' ),
            array('value' => date("Y-m-d", time() - 60 * 60 * 96), 'label' => 'Past 4 Days' ),
            array('value' => date("Y-m-d", time() - 60 * 60 * 120), 'label' => 'Past 5 Days' ),
            array('value' => date("Y-m-d", time() - 60 * 60 * 144), 'label' => 'Past 6 Days' ),
            array('value' => date("Y-m-d", time() - 60 * 60 * 168), 'label' => 'Past 7 Days' ),
        ];
        
        $minDate = RequestReportAPI::select('amazn_report_date')->orderBy('amazn_report_date', 'asc')->first();
        $maxDate = RequestReportAPI::select('amazn_report_date')->orderBy('amazn_report_date', 'desc')->first();

        return response()->json([
            'status' => true,
            'data' => [
                'dates' => $dates,
                'availableDates' => [
                    'minDate' => date('m/d/Y', strtotime($minDate->amazn_report_date . "-1 days")),
                    'maxDate' => date('m/d/Y', strtotime($maxDate->amazn_report_date . "+1 days")),
                ]
            ]
        ]);
    }

}
