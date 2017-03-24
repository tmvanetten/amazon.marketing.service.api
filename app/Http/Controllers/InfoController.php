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
            'endDate' => date("Y-m-d", time() - 60 * 60 * 24), //yesterday
            'beginDate' => date("Y-m-d", time() - 60 * 60 * 24 * 7),  //7 days ago
        ];

        $minDate = RequestReportAPI::select('amazn_report_date')->orderBy('amazn_report_date', 'asc')->first();
        $maxDate = RequestReportAPI::select('amazn_report_date')->orderBy('amazn_report_date', 'desc')->first();

        return response()->json([
            'status' => true,
            'data' => [
                'dates' => $dates,
                'availableDates' => [
                    'minDate' => date('Y-m-d', strtotime($minDate->amazn_report_date)),
                    'maxDate' => date('Y-m-d', strtotime($maxDate->amazn_report_date)),
                ]
            ]
        ]);
    }
}
