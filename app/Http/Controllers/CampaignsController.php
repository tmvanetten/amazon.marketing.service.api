<?php

namespace App\Http\Controllers;

use App\Model\Adgroups;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\RequestReportAPI;
use App\AdGroupsReport;
use App\ProductAdsReport;
use App\KeywordsReport;
use App\NegativeKeywordsReport;
use App\CampaignReport;
use App\Model\Campaigns;
use App\Model\CampaignConnection;
use App\Model\ProductAds;
use App\Model\Keywords;

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
        $result = [
            'counts' => 0,
            'campaigns' => []
        ];
        $beginDate = $request->input('beginDate');
        $endDate = $request->input('endDate');
        $skip = $request->input('skip');
        $rows = $request->input('rows');
        $sortOrder = (int) $request->input('sortOrder') > 0 ? 'asc' : 'desc';
        $criteria = array(
            'globalFilter' => $request->input('globalFilter'),
            'sortField' => $request->input('sortField'),
            'sortOrder' => $sortOrder
        );

        $result['campaigns'] = Campaigns::getCampaigns($criteria, $beginDate, $endDate, $skip, $rows);
        $result['counts'] = Campaigns::getCampaignsCount($criteria, $beginDate, $endDate);

        return response()->json([
            'data' => $result
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
        $sortOrder = (int) $request->input('sortOrder') > 0 ? 'asc' : 'desc';
        $criteria = array(
            'globalFilter' => $request->input('globalFilter'),
            'sortField' => $request->input('sortField'),
            'sortOrder' => $sortOrder
        );

        $result = [
            'adgroups' => [],
            'counts' => 0
        ];

        $result['adgroups'] = Adgroups::getAdgroups($campaignId, $criteria, $beginDate, $endDate, $skip, $rows);
        $result['counts'] = Adgroups::getAdgroupCount($campaignId, $criteria, $beginDate, $endDate);

        return response()->json(['data' => $result]);
    }

    public function getCampaignConnection(Request $request) {
        $campaigns = [];
        $campaignId = $request->input('campaignId');
        try{
            $campaign = Campaigns::find($campaignId);
            if(!$campaign)
                return response()->json(['Campaign not found!'], 400);

            $campaigns[$campaign->targeting_type] = $campaign;
            //get campaign's auto counter part, if it exists
            if($campaign->targeting_type == 'auto') {
                $campaignConnection = CampaignConnection::where('auto_id', $campaign->id)->first();
                if($campaignConnection) {
                    $maunalCampaign = Campaigns::find($campaignConnection->manual_id);
                    if($maunalCampaign)
                        $campaigns[$maunalCampaign->targeting_type] = $maunalCampaign;
                }
            }
            if($campaign->targeting_type == 'manual') {
                $campaignConnection = CampaignConnection::where('manual_id', $campaign->id)->first();
                if($campaignConnection) {
                    $autoCampaign = Campaigns::find($campaignConnection->auto_id);
                    if($autoCampaign)
                        $campaigns[$autoCampaign->targeting_type] = $autoCampaign;
                }
            }
        }catch (\Exception $e) {
            return response()->json([$e->getMessage()], 500);
        }
        return response()->json(['data' => $campaigns], 200);
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
        $sortOrder = (int) $request->input('sortOrder') > 0 ? 'asc' : 'desc';
        $criteria = array(
            'globalFilter' => $request->input('globalFilter'),
            'sortField' => $request->input('sortField'),
            'sortOrder' => $sortOrder
        );

        try{
            $campaign = Campaigns::find($campaignId);
            if(!$campaign)
                return response()->json(['Campaign not found!'], 400);
            $adgroup = Adgroups::find($adGroupId);
            if(!$adgroup)
                return response()->json(['Ad group not found!'], 400);
            $adGroup = [
                'adgroup' => [
                    'id' => $adgroup->id,
                    'adGroupId' => $adgroup->id,
                    'campaignName' =>$campaign->name,
                    'campaignId' =>$campaign->id,
                    'name' => $adgroup->name,
                    'productAds' => ProductAds::getProductAds($campaignId, $adGroupId, $criteria, $beginDate, $endDate, $skip, $rows)
                ],
                'counts' => ProductAds::getProductAdsCount($campaignId, $adGroupId, $criteria)
            ];

        }catch (\Exception $e) {
            return response()->json([$e->getMessage()], 500);
        }
        return response()->json(['data' => $adGroup], 200);
    }

    /**
     * Retrieve  all campaigns.
     * @param $request Request
     * @return json response
     */
    public function getKeywords(Request $request) {
        $result = [
            'keywords' => [],
            'counts' => 0
        ];
        $campaignId = $request->input('campaignId');
        $adGroupId = $request->input('adGroupId');
        $beginDate = $request->input('beginDate');
        $endDate = $request->input('endDate');
        $skip = $request->input('skip');
        $rows = $request->input('rows');
        $sortOrder = (int) $request->input('sortOrder') > 0 ? 'asc' : 'desc';
        $criteria = array(
            'globalFilter' => $request->input('globalFilter'),
            'sortField' => $request->input('sortField'),
            'sortOrder' => $sortOrder
        );
        try{
            $result['keywords'] = Keywords::getKeywords($campaignId, $adGroupId, $criteria, $beginDate, $endDate, $skip, $rows);
            $result['counts'] = Keywords::getKeywordsCount($campaignId, $adGroupId, $criteria);
        }catch (\Exception $e) {
            return response()->json([$e->getMessage()], 500);
        }
        return response()->json(['data' => $result], 200);
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
        $sortOrder = (int) $request->input('sortOrder') > 0 ? 'asc' : 'desc';
        $criteria = array(
            'globalFilter' => $request->input('globalFilter'),
            'sortField' => $request->input('sortField'),
            'sortOrder' => $sortOrder
        );

        $counts = 0;

        $adgroup = AdGroupsReport::where('adGroupId', $adGroupId)->where('campaignId',  $campaignId)->first();

        $query = NegativeKeywordsReport::where('ad_group_id', $adgroup->id);//where('campaignId', $campaignId)->

        if(!empty($criteria['globalFilter'])) $query = $query->where('keywordText', 'LIKE', '%' . $criteria['globalFilter'] . '%');

        if($criteria['sortField'] && $criteria['sortOrder'])
            $query = $query->orderBy($criteria['sortField'], $criteria['sortOrder']);

        $counts = count($query->distinct()->get());
        if(!is_null($skip) || !is_null($rows)){
            $nkeyWords = $query->offset($skip)->limit($rows)->distinct()->get();
        }else{
            $nkeyWords = $query->distinct()->get();
        }

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
