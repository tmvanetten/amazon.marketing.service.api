<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
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
     * @param $request Request
     * @return json response
     */
    public function index(Request $request) {
        $beginDate = $request->input('beginDate');
        $endDate = $request->input('endDate');
        $skip = $request->input('skip');
        $rows = $request->input('rows');
        $reports_ids = $this->_getReportId($beginDate, $endDate, 'campaigns');
        $counts = count(CampaignReport::getCampaigns($reports_ids));

        DB::connection()->enableQueryLog();

        $campaigns = CampaignReport::getCampaigns($reports_ids, $skip, $rows);

        foreach($campaigns as $campaign){
            $reportIds = $this->_getReportId($beginDate, $endDate, 'adGroups');
            if(count($reportIds)) {
                $campaign->adGroupsCount = count(AdGroupsReport::where('campaignId', $campaign->campaignId)
                    ->where('request_report_id', $reportIds[0])->get());
            } else {
                $campaign->adGroupsCount = 0;
            }

        }

        return response()->json([
            'status' => true,
            'data' => [
                'campaigns' => $campaigns,
                'counts' => $counts
            ]
        ]);
    }

    /**
     * Retrieve  all campaigns.
     * @param $request Request
     * @return json response
     */
    public function getCampaignById(Request $request) {
        $campaignId = $request->input('campaignId');
        $beginDate = $request->input('beginDate');
        $endDate = $request->input('endDate');
        $skip = $request->input('skip');
        $rows = $request->input('rows');

        $campaign = [
            'campaign' => [],
            'counts' => 0
        ];

        $campaignData = CampaignReport::where('campaignId', $campaignId)
            ->whereIn('request_report_id', $this->_getReportId($beginDate, $endDate, 'campaigns'))
            ->orderBy('created_at', 'DESC')
            ->first();

        if($campaignData) {
            $reportIds = $this->_getReportId($beginDate, $endDate, 'adGroups');
            $adgroups = AdGroupsReport::getAdgroups($reportIds, $campaignId, $skip, $rows);
            $counts = count(AdGroupsReport::getAdgroups($reportIds, $campaignId));
            $campaign = [
                'campaign' => [
                    'id' => $campaignData->id,
                    'campaignId' => $campaignData->campaignId,
                    'name' => $campaignData->name,
                    'request_report_id' => $campaignData->request_report_id,
                    'adGroups' => $adgroups
                ],
                'counts' => $counts,
            ];
        }

        return response()->json([
            'status' => true,
            'data' => $campaign
        ]);
    }

    /**
     * Retrieve  all campaigns.
     * @param $request Request
     * @return json response
     */
    public function getProductAds(Request $request) {
        $campaignId = $request->input('campaignId');
        $adGroupId = $request->input('adGroupId');
        $beginDate = $request->input('beginDate');
        $endDate = $request->input('endDate');
        $skip = $request->input('skip');
        $rows = $request->input('rows');

        $adGroup = [
            'adgroup' => [],
            'counts' => 0
        ];

        $campaign = CampaignReport::where('campaignId', $campaignId)
            ->whereIn('request_report_id', $this->_getReportId($beginDate, $endDate, 'campaigns'))
            ->orderBy('created_at', 'DESC')
            ->first();
        if($campaign) {
            $adgroupData = AdGroupsReport::where('adGroupId', $adGroupId)
                ->whereIn('request_report_id', $this->_getReportId($beginDate, $endDate, 'adGroups'))
                ->orderBy('created_at', 'DESC')
                ->first();

            if($adgroupData){
                $reportIds = $this->_getReportId($beginDate, $endDate, 'productAds');

                $productAdsData = ProductAdsReport::getProductAds($reportIds, $campaignId, $adGroupId, $skip, $rows);

                $counts = count(ProductAdsReport::getProductAds($reportIds, $campaignId, $adGroupId));

                $adGroup = [
                    'adgroup' => [
                        'id' => $adgroupData->id,
                        'adGroupId' => $adgroupData->adGroupId,
                        'campaignName' =>$campaign->name,
                        'campaignId' =>$campaign->campaignId,
                        'name' => $adgroupData->name,
                        'request_report_id' => $adgroupData->request_report_id,
                        'productAds' => $productAdsData
                    ],
                    'counts' => $counts
                ];
            }
        }

        return response()->json([
            'status' => true,
            'data' => $adGroup
        ]);
    }

    /**
     * Retrieve  all campaigns.
     * @param $request Request
     * @return json response
     */
    public function getKeywords(Request $request) {
        $campaignId = $request->input('campaignId');
        $adGroupId = $request->input('adGroupId');
        $beginDate = $request->input('beginDate');
        $endDate = $request->input('endDate');
        $skip = $request->input('skip');
        $rows = $request->input('rows');

        $reportIds = $this->_getReportId($beginDate, $endDate, 'keywords');

        $keyWords = KeywordsReport::getKeywords($reportIds, $campaignId, $adGroupId, $skip, $rows);

        $counts = count(KeywordsReport::getKeywords($reportIds, $campaignId, $adGroupId));

        return response()->json([
            'status' => true,
            'data' => [
                'keywords' => $keyWords,
                'counts' => $counts
            ]
        ]);
    }

    /**
     * Retrieve  negative keywords.
     * @param $request Request
     * @return json response
     */
    public function getNegativeKeywords(Request $request) {
        $campaignId = $request->input('campaignId');
        $adGroupId = $request->input('adGroupId');
        $beginDate = $request->input('beginDate');
        $endDate = $request->input('endDate');
        $skip = $request->input('skip');
        $rows = $request->input('rows');

        $query = NegativeKeywordsReport::where('adGroupId', $adGroupId);//where('campaignId', $campaignId)->->where('ad_group_id',  $adGroup->id)

        if(!is_null($skip) || !is_null($rows)){
            $nkeyWords = $query->offset($skip)->limit($rows)->get();
        }else{
            $nkeyWords = $query->get();
        }

        $counts = count($nkeyWords);
        return response()->json([
            'status' => true,
            'data' => [
                'nkeywords' => $nkeyWords,
                'counts' => $counts
            ]
        ]);
    }

    /**
     * Retrieve  all campaigns.
     * @param $request Request
     * @return json response
     */
    public function enableDisable(Request $request) {
        $id = $request->input('id');
        $enabled = $request->input('enabled');
        $type = $request->input('type');
        $model = null;
        $state = $enabled == 1 ? 'enabled' : 'disabled';
        switch($type){
            case 'campaign':
                $model = new CampaignReport();
                $modelId = 'campaignId';
                break;
            case 'adGroup':
                $model = new AdGroupsReport();
                $modelId = 'adGroupId';
                break;
            case 'productAd':
                $model = new ProductAdsReport();
                $modelId = 'adId';
                break;
            case 'keyword':
                $model = new KeywordsReport();
                $modelId = 'keywordId';
                break;
            case 'negativeKeyword':
                $model = new NegativeKeywordsReport();
                $modelId = 'keywordId';
                break;
        }

        if ($model->where($modelId, $id)->update(['enabled' => $enabled])){
            return response()->json([
                'status' => true,
                'data' => [
                    'message' => 'Item has been ' . $state,
                    'severity' => 'success'
                ]
            ]);
        }else{
            return response()->json([
                'status' => true,
                'data' => [
                    'message' => "Item status was not "  . $state,
                    'severity' => 'error'
                ]
            ]);
        }

    }

    /**
     * Get  report id by date.
     * @param $beginDate string
     * @param $endDate string
     * @param $type string
     * @return json response
     */
    protected function _getReportId($beginDate, $endDate, $type) {
        $report =  RequestReportAPI::where('type', $type)
            ->whereBetween('amazn_report_date', [$beginDate,$endDate])
            ->orderBy('amazn_report_date', 'DESC')
            ->get();
        $result = [];
        foreach($report as $report_item){
            $result[] = $report_item->id;
        }
        return $result;
    }

}
