<?php

namespace App\Http\Controllers;

use App\Model\Adgroups;
use App\Model\ProductAds;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\RequestReportAPI;
use App\AdGroupsReport;
use App\KeywordsReport;
use App\NegativeKeywordsReport;
use App\Model\Campaigns;
use App\Model\CampaignConnection;
use App\Model\Keywords;
use App\Model\StrategyHistory;

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
        $filters = $request->input('filters');
        $sortOrder = (int) $request->input('sortOrder') > 0 ? 'asc' : 'desc';
        $criteria = array(
            'globalFilter' => $request->input('globalFilter'),
            'sortField' => $request->input('sortField'),
            'sortOrder' => $sortOrder,
            'filters' => $filters
        );

        $result['campaigns'] = Campaigns::getCampaigns($criteria, $beginDate, $endDate, $skip, $rows);
        $result['counts'] = count(Campaigns::getCampaigns($criteria, $beginDate, $endDate));

        return response()->json([
            'data' => $result
        ]);
    }

    public function getAdGroupHistory(Request $request) {
        $adgroupId = $request->input('adgroupId');
        $result = [
            'lineChartData' => [
                'bid' => [],
                'clicks' => [],
                'cost' => [],
                'sales' => []
            ],
            'lineChartLabels' => [],
            'histories' => []
        ];
        try {
            $adgroup = Adgroups::find($adgroupId);
            if(!$adgroup)
                return response()->json(['Adgroup not found!'], 400);
            $endDate  = date("Y-m-d");
            $beginDate = date("Y-m-d", strtotime('-15 days'));
            $histories = StrategyHistory::query();
            $histories->LeftJoin('campaigns', function ($join){
                $join->on( 'campaigns.id', '=', 'strategy_history.campaign_id');
            });
            $histories = $histories->where('campaigns.targeting_type', 'auto')
                ->where('strategy_history.ad_group_id', $adgroup->id)
                ->where('strategy_history.created_at', '>=', $beginDate)
                ->where('strategy_history.created_at', '<=', $endDate)
                ->get(['strategy_history.created_at', 'strategy_history.updated_by']);
            $processedHistories = [];
            foreach($histories as $history) {
                $processedHistories[ date("Y-m-d", strtotime($history->created_at))] = $history->updated_by;
            }
            $keywords = Adgroups::getAdGroupsHistory($adgroup->id, $beginDate, $endDate);
            foreach($keywords as $keyword) {
                $result['lineChartData']['bid'][] = $keyword->bid;
                $result['lineChartData']['clicks'][] = $keyword->clicks;
                $result['lineChartData']['cost'][] = $keyword->cost;
                $result['lineChartData']['sales'][] = $keyword->sales;
                $result['lineChartLabels'][] = $keyword->amazn_report_date;
            }
            foreach ($result['lineChartLabels'] as $date) {
                if(isset($processedHistories[$date])) {
                    $result['histories'][] = $processedHistories[$date];
                } else {
                    $result['histories'][] = false;
                }
            }
        } catch  (\Exception $e) {
            var_dump($e);
            return response()->json(['errors' => [$e->getMessage()]], 422);
        }
        return response()->json(['data' => $result], 200);
    }

    public function getKeywordHistory(Request $request) {
        $keywordId = $request->input('keywordId');
        $result = [
            'lineChartData' => [
                'bid' => [],
                'clicks' => [],
                'cost' => [],
                'sales' => []
            ],
            'lineChartLabels' => [],
            'histories' => []
        ];
        try {
            $keyword = Keywords::find($keywordId);
            if(!$keyword)
                return response()->json(['Keyword not found!'], 400);
            $endDate  = date("Y-m-d");
            $beginDate = date("Y-m-d", strtotime('-15 days'));
            $histories = StrategyHistory::where('keyword_id', $keyword->id)
                ->where('created_at', '>=', $beginDate)
                ->where('created_at', '<=', $endDate)
                ->get(['created_at', 'updated_by']);
            $processedHistories = [];
            foreach($histories as $history) {
                $processedHistories[ date("Y-m-d", strtotime($history->created_at))] = $history->updated_by;
            }
            $keywords = Keywords::getKeyWordsHistory($keyword->id, $beginDate, $endDate);
            foreach($keywords as $keyword) {
                $result['lineChartData']['bid'][] = $keyword->bid;
                $result['lineChartData']['clicks'][] = $keyword->clicks;
                $result['lineChartData']['cost'][] = $keyword->cost;
                $result['lineChartData']['sales'][] = $keyword->sales;
                $result['lineChartLabels'][] = $keyword->amazn_report_date;
            }
            foreach ($result['lineChartLabels'] as $date) {
                if(isset($processedHistories[$date])) {
                    $result['histories'][] = $processedHistories[$date];
                } else {
                    $result['histories'][] = false;
                }
            }
        } catch  (\Exception $e) {
            return response()->json(['errors' => [$e->getMessage()]], 422);
        }
        return response()->json(['data' => $result], 200);
    }

    /**
 * Retrieve  two lists of campaigns for dropdowns.
 * @return json response
 */
    public function listCampaigns() {
        try {
            $manualCampaigns = Campaigns::select('id as value','name as label')->where('targeting_type', 'manual')->whereNotIn('id', function ($query) {
                $query->select(DB::raw('manual_id'))
                    ->from('campaign_connection');
            })->get();
            $autoCampaigns = Campaigns::select('id as value','name as label')->where('targeting_type', 'auto')->whereNotIn('id', function ($query) {
                $query->select(DB::raw('auto_id'))
                    ->from('campaign_connection');
            })->get();
            $result = [
                'autoCampaigns' => $autoCampaigns,
                'manualCampaigns' => $manualCampaigns
            ];
        } catch  (\Exception $e) {
            return response()->json(['errors' => [$e->getMessage()]], 422);
        }
        return response()->json(['data' => $result], 200);
    }

    /**
     * Get connected campaigns.
     * @return json response
     */
    public function getConnectedCampaigns(Request $request) {

        $skip = $request->input('skip');
        $rows = $request->input('rows');
        $manual_name_filter = $request->input('manual_name_filter');
        $auto_name_filter = $request->input('auto_name_filter');
        $sortOrder = (int) $request->input('sortOrder') > 0 ? 'asc' : 'desc';
        $criteria = array(
            'filters' => array('a.name' => $manual_name_filter, 'b.name' => $auto_name_filter),
            'sortField' => $request->input('sortField'),
            'sortOrder' => $sortOrder
        );

        try {

            $connectedCampaigns = CampaignConnection::getConnectedCampaigns($criteria, $skip, $rows);
            $counts = CampaignConnection::getConnectedCampaigns($criteria);
            $result = [
                'connectedCampaigns' => $connectedCampaigns,
                'counts' => count($counts)
            ];
        } catch  (\Exception $e) {
            return response()->json(['errors' => [$e->getMessage()]], 422);
        }
        return response()->json(['data' => $result], 200);
    }

    /**
     * Automatically connect campaigns.
     * @return json response
     */
    public function automaticConnect() {

        $manualIds = $this->_getNotConnectedCampaigns('manual');
        $automaticIds = $this->_getNotConnectedCampaigns('auto');
        $count = 0;
        $result['manual'] = [];
        $result['manual_id'] = $manualIds;//[];
        $result['auto'] = [];
        $result['auto_id'] = $automaticIds;//[];
        foreach($manualIds as $idx => $manual_id){
            $manualSKUs = $this->_getProductAdsASINsByCampaignId($manual_id)->toArray();
            sort($manualSKUs);
            if(empty($manualSKUs)){
                unset($manualIds[$idx]);
                continue;
            }
            foreach($automaticIds as $index => $auto_id){
                $autoSKUs = $this->_getProductAdsASINsByCampaignId($auto_id)->toArray();
                sort($autoSKUs);
                if(empty($autoSKUs)){
                    unset($autoSKUs[$index]);
                    continue;
                }
                if($manualSKUs == $autoSKUs){
                    try {
                        //IMPORTANT uncomment to implement connection of campaigns automatically
                        CampaignConnection::create(['manual_id' => $manual_id, 'auto_id' => $auto_id]);
                        $count++;
                    } catch  (\Exception $e) {
                        return response()->json(['errors' => [$e->getMessage()]], 422);
                    }
                }
                $result['manual'][$manual_id] = $manualSKUs;
                $result['auto'][$auto_id] = $autoSKUs;

                $autoSKUs = null;
            }
            $manualSKUs = null;
        }
        return response()->json(['data' => array('message' => $count . ' connections has been created!', 'result' => $result)], 200);
    }

    /**
     * Make a connection between two campaigns.
     * @param $request Request
     * @return json response
     */
    public function manuallyConnectCampaigns(Request $request) {
        $manual_id = $request->input('selectedManualCampaign');
        $auto_id = $request->input('selectedAutoCampaign');
        try {
            CampaignConnection::create(['manual_id' => $manual_id, 'auto_id' => $auto_id]);
            $result = ['message' => 'Campaigns were successfully connected!'];
        } catch  (\Exception $e) {
            return response()->json(['errors' => [$e->getMessage()]], 422);
        }
        return response()->json(['data' => $result], 200);
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
            'filters' => $request->input('filters'),
            'sortField' => $request->input('sortField'),
            'sortOrder' => $sortOrder
        );

        $result = [
            'adgroups' => [],
            'counts' => 0
        ];

        $result['adgroups'] = Adgroups::getAdgroups($campaignId, $criteria, $beginDate, $endDate, $skip, $rows);
        $result['counts'] = count(Adgroups::getAdgroups($campaignId, $criteria, $beginDate, $endDate));

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

    protected function _getNotConnectedCampaigns ($type){
        $connectId = $type === 'manual' ? 'manual_id' : 'auto_id';
        return Campaigns::where('targeting_type', $type)->whereNotIn('id', function ($query) use ($connectId) {
            $query->select(DB::raw($connectId))
                ->from('campaign_connection');
        })->pluck('id');
    }

    /**
     * Retrieve  all campaigns.
     * @param $id Request
     * @return array
     */
    protected function _getProductAdsASINsByCampaignId($id){
        return ProductAds::where('campaign_id', $id)->pluck('asin');
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
            'filters' => $request->input('filters'),
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
                'counts' => count(ProductAds::getProductAds($campaignId, $adGroupId, $criteria, $beginDate, $endDate))
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
            'filters' => $request->input('filters'),
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
            'filters' => $request->input('filters'),
            'sortField' => $request->input('sortField'),
            'sortOrder' => $sortOrder
        );

        $counts = 0;

        $adgroup = AdGroupsReport::where('adGroupId', $adGroupId)->where('campaignId',  $campaignId)->first();

        $query = NegativeKeywordsReport::where('ad_group_id', $adgroup->id);//where('campaignId', $campaignId)->

        if(!empty($criteria['globalFilter'])) $query = $query->where('keywordText', 'LIKE', '%' . $criteria['globalFilter'] . '%');
        if($criteria['filters'] && is_array($criteria['filters']) && count($criteria['filters'])) {
            foreach($criteria['filters'] as $field => $filter) {
                switch($filter['matchMode']) {
                    case 'like':
                        $query = $query->where($field, 'LIKE', '%' . $filter['value'] . '%');
                        break;
                    case 'equals':
                        $query = $query->where($field, '=', $filter['value']);
                        break;
                    case 'in':
                        $query = $query->whereBetween($field, $filter['value']);
                }
            }
        }

        if($criteria['sortField'] && $criteria['sortOrder'])
            $query = $query->orderBy($criteria['sortField'], $criteria['sortOrder']);

        $counts = count($query->distinct()->get());
        if(!is_null($skip) || !is_null($rows)){
            $nkeyWords = $query->offset($skip)->limit($rows)->distinct()->get();
        }else{
            $nkeyWords = $query->distinct()->get();
        }

        return response()->json([
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
                $model = new Campaigns();
                $modelId = 'id';
                break;
        }

        if ($model->where($modelId, $id)->update(['run_strategy' => $enabled])){
            return response()->json([
                'data' => [
                    'message' => 'Item has been ' . $state,
                    'severity' => 'success'
                ]
            ]);
        }else{
            return response()->json([
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
