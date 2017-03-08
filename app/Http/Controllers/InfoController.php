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
            array('value' => date("Ymd", time() - 60 * 60 * 24), 'label' => 'Yesterday' ),
            array('value' => date("Ymd", time() - 60 * 60 * 48), 'label' => 'Past 2 Days' ),
            array('value' => date("Ymd", time() - 60 * 60 * 72), 'label' => 'Past 3 Days' ),
            array('value' => date("Ymd", time() - 60 * 60 * 96), 'label' => 'Past 4 Days' ),
            array('value' => date("Ymd", time() - 60 * 60 * 120), 'label' => 'Past 5 Days' ),
            array('value' => date("Ymd", time() - 60 * 60 * 144), 'label' => 'Past 6 Days' ),
            array('value' => date("Ymd", time() - 60 * 60 * 168), 'label' => 'Past 7 Days' ),
        ];
        return response()->json([
            'status' => true,
            'dates' => $dates
        ]);
    }

}
