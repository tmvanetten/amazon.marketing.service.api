<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\RequestReportAPI;
use App\AdGroupsReport;
use App\ProductAdsReport;
use App\KeywordsReport;
use App\NegativeKeywordsReport;
use App\CampaignReport;
class CampaignsController extends Controller
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
     * Retrieve  all campaigns.
     * @param $date string
     * @return json response
     */
    public function index($date) {
        $campaigns = CampaignReport::getCampaigns($date);
        return response()->json([
            'status' => true,
            'campaigns' => $campaigns
        ]);
    }

    /**
     * Retrieve  all campaigns.
     * @param $campaignId number
     * @param $date string
     * @return json response
     */
    public function getCampaignById($campaignId, $selectedDate) {

        $campaignData = CampaignReport::where('campaignId', $campaignId)
            ->where('request_report_id', $this->_getReportId($selectedDate, 'campaigns'))->first();

        $adgroups = AdGroupsReport::where('campaignId', $campaignId)
            ->where('request_report_id', $this->_getReportId($selectedDate, 'adGroups'))->get();

        $campaign = [
            'id' => $campaignData->id,
            'campaignId' => $campaignData->campaignId,
            'name' => $campaignData->name,
            'request_report_id' => $campaignData->request_report_id,
            'adGroups' => $adgroups
        ];

        return response()->json([
            'status' => true,
            'campaign' => $campaign
        ]);
    }

    /**
     * Retrieve  all campaigns.
     * @param $campaignId number
     * @param $selectedDate string
     * @return json response
     */
    public function getProductAd($campaignId, $adgroupId, $selectedDate) {

        $campaign = CampaignReport::where('campaignId', $campaignId)->first();
        $adgroupData = AdGroupsReport::where('adGroupId', $adgroupId)
            ->where('request_report_id', $this->_getReportId($selectedDate, 'adGroups'))->first();

        $productAdsData = ProductAdsReport::where('campaignId', $campaignId)
            ->where('adGroupId', $adgroupId)->where('request_report_id',  $this->_getReportId($selectedDate, 'productAds'))->get();

        $adGroup = [
            'id' => $adgroupData->id,
            'adGroupId' => $adgroupData->adGroupId,
            'campaignName' =>$campaign->name,
            'campaignId' =>$campaign->campaignId,
            'name' => $adgroupData->name,
            'request_report_id' => $adgroupData->request_report_id,
            'productAds' => $productAdsData
        ];

        return response()->json([
            'status' => true,
            'adgroup' => $adGroup
        ]);
    }

    /**
     * Retrieve  all campaigns.
     * @param $campaignId number
     * @param $selectedDate string
     * @return json response
     */
    public function getKeywords($campaignId, $adgroupId, $selectedDate) {
        $keyWords = KeywordsReport::where('campaignId', $campaignId)
            ->where('adGroupId', $adgroupId)
            ->where('request_report_id',  $this->_getReportId($selectedDate, 'keywords'))
            ->get();
        return response()->json([
            'status' => true,
            'keywords' => $keyWords
        ]);
    }

    /**
     * Retrieve  negative keywords.
     * @param $campaignId number
     * @param $adgroupId number
     * @param $selectedDate string
     * @return json response
     */
    public function getNKeywords($campaignId, $adgroupId, $selectedDate) {
        $adGroup = AdGroupsReport::where('adGroupId', $adgroupId)
            ->where('request_report_id',  $this->_getReportId($selectedDate, 'adGroups'))->first();
        $nkeyWords = NegativeKeywordsReport::where('campaignId', $campaignId)
            ->where('adGroupId', $adgroupId)
            ->where('ad_group_id',  $adGroup->id)
            ->get();
        return response()->json([
            'status' => true,
            'nkeywords' => $nkeyWords
        ]);
    }

    /**
     * Get  report id by date.
     * @param $date string
     * @param $type string
     * @return json response
     */
    protected function _getReportId($date, $type) {
        $report =  RequestReportAPI::where('type', $type)
            ->where('amazn_report_date', $date)->first();
        return $report->id;
    }

}
