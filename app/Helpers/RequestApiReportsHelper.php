<?php

namespace App\Helpers;

require_once base_path('vendor/amzn/amazon-advertising-api-php/AmazonAdvertisingApi/Client.php');

use AmazonAdvertisingApi\Client;
use App\RequestReportAPI;
class RequestApiReportsHelper
{
    /**
     * Past seven days date.
     *
     * @var string
     */
    protected $_past_seven_days_date;

    /**
     * Past thirty days date.
     *
     * @var string
     */
    protected $_past_thirty_days_date;

    /**
     * Past n-th day date.
     *
     * @var string
     */
    protected $_past_day;

    /**
     * Amazon config data.
     *
     * @var array
     */
    protected $_amazon_config;

    /**
     * Report types.
     *
     * @var array
     */
    protected $_report_types;


    public function __construct()
    {
        $this->_past_seven_days_date = $this->_getPastDayDate(7);
        $this->_past_thirty_days_date = $this->_getPastDayDate(30);
        $this->_amazon_config = $this->_getAmazonConfig();
        $this->_report_types = $this->_getReportTypes();
    }

    /**
     * Get past day date.
     * @param $pastDay integer
     * @return string
     */
    protected function _getPastDayDate($pastDay)
    {
        return date("Ymd", time() - 60 * 60 * ($pastDay * 24));
    }

    /**
     * Get Amazon config data.
     * @return array
     */
    protected function _getAmazonConfig()
    {
        return [
            "clientId" => env('AMAZN_ID'),
            "clientSecret" => env('AMAZN_SECRET'),
            "refreshToken" => env('AMAZN_TOKEN'),
            "region" => env('AMAZN_REGION'),
            "sandbox" => env('AMAZN_SANDBOX')
        ];
    }

    /**
     * Get Report types.
     * @return array
     */
    protected function _getReportTypes()
    {
        return array(
            'campaigns',
            'adGroups',
            'keywords',
            'productAds',
        );
    }
    /**
     * Request reports from amazon and save returned data to database.
     * @return array
     */
    public function requestAmazonReports()
    {
        $quantity = 0;
        $client = new Client($this->_amazon_config);
        $request = $client->getProfiles();
        if ($request['success'] != 1) {
            return 'Error: Not any profile found.';
        }
        $data = $request['response'];
        $profileData = json_decode($data);
        foreach($profileData as $profile) {
            $client->profileId = $profile->profileId;
            foreach($this->_report_types as $reportType) {
                $day = 1;
                while($this->_getPastDayDate($day) >= $this->_past_seven_days_date){
                    $this->_past_day = $this->_getPastDayDate($day);
                    $pastDayReport = $this->_requestPastDayReport($client, $profile, $reportType);
                    if(!$pastDayReport['status']) return $pastDayReport;
                    $quantity++;
                    $day++;
                }
            }
        }
        $this->_deleteOlderThan30Days();
        return array(
          'status' => true,
          'message' => 'Ok',
          'quantity' => $quantity
        );
    }

    /**
     * Request one day report from amazon by date.
     * @param $client Client
     * @param $profile \ClassWithScalarTypeDeclarations
     * @param $reportType string
     * @return array
     */
    protected function _requestPastDayReport($client, $profile, $reportType)
    {
        $request = $client->requestReport($reportType, array(
            'campaignType' => 'sponsoredProducts',
            'reportDate' => $this->_past_day,
            'metrics' => 'impressions,clicks,cost,attributedConversions1dSameSKU,attributedConversions1d,attributedSales1dSameSKU,attributedSales1d'));
        $data = $request['response'];
        $report = json_decode($data);
        if (!empty($report->reportId)) {
            $reportItem = $this->_getPastReport($profile->profileId, $reportType);
            if(!is_null($reportItem)){
                $reportItem->type = $reportType;
                $reportItem->report_date = date('Y-m-d');
                $reportItem->amazn_profile_id = $client->profileId;
                $reportItem->amazn_report_id = $report->reportId;
                $reportItem->amazn_report_date = $this->_past_day;
                $reportItem->amazn_record_type = $report->recordType;
                $reportItem->amazn_status = 'IN_PROGRESS'; //$report->status;
                $reportItem->amazn_status_details = $report->statusDetails;
                $reportItem->save();
            }else{
                $reportItem = new RequestReportAPI;
                $reportItem->type = $reportType;
                $reportItem->report_date = date('Y-m-d');
                $reportItem->amazn_profile_id = $client->profileId;
                $reportItem->amazn_report_id = $report->reportId;
                $reportItem->amazn_report_date = $this->_past_day;
                $reportItem->amazn_record_type = $report->recordType;
                $reportItem->amazn_status =  'IN_PROGRESS'; //$report->status;
                $reportItem->amazn_status_details = $report->statusDetails;
                $reportItem->save();
            }
            return array(
                'status' => true,
                'message' => 'Done'
            );
        }  else {
            return array(
                'status' => false,
                'message' => 'Error: Not generate report request. Amazon error: '.$report->statusDetails
            );
        }

    }

    /**
     * Get previous report from database by profileId and reportType.
     * @param $profileId string
     * @param $reportType string
     * @return RequestReportAPI
     */
    protected function _getPastReport($profileId, $reportType){
        return RequestReportAPI::where('amazn_profile_id', $profileId)
            ->where('type', $reportType)
            ->where('amazn_report_date', $this->_past_day)
            ->first();
    }

    /**
     * Delete data that has been stored over 30 days.
     * @return boolean
     */
    protected function _deleteOlderThan30Days(){
        $items = RequestReportAPI::where('amazn_report_date', '<', $this->_past_thirty_days_date)->delete();
        return $items;
    }
}