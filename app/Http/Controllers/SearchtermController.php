<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use Illuminate\Http\Request;
use App\Model\Searchterm;
use App\Model\Campaigns;
use App\Model\Adgroups;
use App\Model\CampaignConnection;
use App\Model\SearchtermUploadException;
use App\Model\SearchTermHistory;
use App\Model\SearchTermDate;
use App\Helpers\AmazonAPI;
use DB;

class SearchtermController extends Controller
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

    public function getDateModel() {
        $model = SearchTermDate::first();
        if(!$model)
            $model = new SearchTermDate();
        return $model;
    }

    /**
     * get search terms data
     *
     * @return json response
     */
    public function get(Request $request) {
        $result = [
            'searchterms' => [],
            'count' => 0,
            'beginDate' => '',
            'endDate' => ''
        ];
        $skip = $request->input('skip');
        $rows = $request->input('rows');
        $sortOrder = (int) $request->input('sortOrder') > 0 ? 'asc' : 'desc';
        $criteria = array(
            'globalFilter' => $request->input('globalFilter'),
            'filters' => $request->input('filters'),
            'sortField' => $request->input('sortField'),
            'sortOrder' => $sortOrder
        );
        $campaingName = $request->input('campaingName');
        $adGroupName = $request->input('adGroupName');
        $keyWordName = $request->input('keyWordName');
        $matchType = $request->input('matchType');
        try{
            $model = new Searchterm;
            if($campaingName) {
                $campaingNames = explode(',', $campaingName);
                $model = $model ->whereIn('campaign_name', $campaingNames);
            }
            if($adGroupName || $keyWordName) {
                $wheres = [];
                if($adGroupName)
                    $wheres['adgroup_name'] = $adGroupName;
                if($keyWordName)
                    $wheres['keyworod'] = $keyWordName;
                if($matchType)
                    $wheres['match_type'] = $matchType;
                $model = $model->where($wheres);
            }
            if($criteria['globalFilter'])
                $model = $model->where('customer_search_term', 'LIKE', '%' . $criteria['globalFilter'] . '%');
            if($criteria['filters'] && is_array($criteria['filters']) && count($criteria['filters'])) {
                foreach($criteria['filters'] as $field => $filter) {
                    switch($filter['matchMode']) {
                        case 'like':
                            $model = $model->where($field, 'LIKE', '%' . $filter['value'] . '%');
                            break;
                        case 'equals':
                            $model = $model->where($field, '=', $filter['value']);
                            break;
                        case 'in':
                            $model = $model->whereBetween($field, $filter['value']);
                            break;
                    }
                }
            }
            $result['count'] = $model->count();
            if($criteria['sortField'] && $criteria['sortOrder'])
                $model = $model->orderBy($criteria['sortField'], $criteria['sortOrder']);

            $model->select('*');
            $model->addSelect(DB::raw('(select count(*) from search_term_history WHERE search_terms_report.customer_search_term = search_term_history.search_term) as history_count'));
            $result['searchterms'] = $model->offset($skip)->limit($rows)->get();
            $dateModel = $this->getDateModel();
            $result['beginDate'] = $dateModel->beginDate;
            $result['endDate'] = $dateModel->endDate;
        }catch (\Exception $e) {
            return response()->json(['errors' => [$e->getMessage()]], 422);
        }
        return response()->json(['data' => $result], 200);
    }

    /**
     * crate positive keyword
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function createPositiveKeyword(Request $request) {
        $adgroupId = $request->input('adgroupId');
        $campaignId = $request->input('campaignId');
        $matchType = $request->input('matchType');
        $searchTerm = trim($request->input('searchTerm'));

        if(!$adgroupId)
            return response()->json(['Please select a Ad Group!'], 400);
        if(!$campaignId)
            return response()->json(['Please select a Campaign!'], 400);
        if(!$matchType)
            return response()->json(['Please select a match type!'], 400);
        if(!$searchTerm)
            return response()->json(['Please enter a search term!'], 400);

        $campaign = Campaigns::find($campaignId);
        if(!$campaign)
            return response()->json(['Campaign not found!'], 400);

        $adgroup = Adgroups::find($adgroupId);
        if(!$adgroup)
            return response()->json(['Ad Group not found!'], 400);

        try{
            //call amazon api to create a new keyword
            $amazonAPI = new AmazonAPI;
            $keywords = [
                    'campaignId' => $campaignId,
                    'adGroupId' => $adgroupId,
                    'keywordText' => $searchTerm,
                    'matchType' => $matchType,
                    'state' => 'enabled'
                ];
            $responses = $amazonAPI->createPositiveKeyword($keywords);
            $campaignName = $campaign->name;
            $adgroupName = $adgroup->name;
            if($responses[0]['code'] != 'DUPLICATE') {
                $message = "Successfully created added keyword $searchTerm to $campaignName > $adgroupName, match type: $matchType";
                //create history entry for the action
                SearchTermHistory::create([
                    'search_term' => $searchTerm,
                    'message' => $message,
                    'type' => 'positive',
                    'campaign_id' => $campaignId,
                    'ad_group_id' => $adgroupId,
                    'match_type' => $matchType
                ]);
            } else {
                $message = "Keyword $searchTerm was already added to $campaignName > $adgroupName, match type: $matchType";
            }
        }catch (\Exception $e) {
            return response()->json([$e->getMessage()], 500);
        }
        return response()->json(['data' => [$message]], 200);

    }

    /**
     * create negative keywords
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function createNegativeKeyword(Request $request) {
        $campaignId = $request->input('campaignId');
        $matchType = $request->input('matchType');
        $searchTerm = trim($request->input('searchTerm'));

        if(!$campaignId)
            return response()->json(['Please select a Campaign!'], 400);
        if(!$matchType)
            return response()->json(['Please select a match type!'], 400);
        if(!$searchTerm)
            return response()->json(['Please enter a search term!'], 400);

        $campaign = Campaigns::find($campaignId);
        if(!$campaign)
            return response()->json(['Campaign not found!'], 400);

        try{
            $amazonAPI = new AmazonAPI;
            $keywords = [];
            $entries = [];
            $campaigns = [$campaign];
            $messages = [];
            //get manual campaign's auto counter part, if it exists
            $campaignConnection = CampaignConnection::where('manual_id', $campaign->id)->first();
            if($campaignConnection) {
                $autoCampaign = Campaigns::find($campaignConnection->auto_id);
                if($autoCampaign)
                    $campaigns[] = $autoCampaign;
            }

            foreach($campaigns as $campaign) {
                $adgroups = $campaign->adgroupsSimple;
                foreach($adgroups as $adgroup) {
                    $entries[] = [
                        'campaignName' =>  $campaign->name,
                        "campaignId" => $campaign->id,
                        'adgroupName' =>  $adgroup->name,
                        "adGroupId" => $adgroup->id,
                    ];
                    $keywords[] = [
                        "campaignId" => $campaign->id,
                        "adGroupId" => $adgroup->id,
                        "keywordText" => $searchTerm,
                        "matchType" => $matchType,
                        "state" => "enabled"
                    ];
                }
            }
            //call amazon api to create a new keyword
            $responses = $amazonAPI->createNegativeKeywords($keywords);
            foreach($responses as $index => $response) {
                if(isset($entries[$index])) {
                    $entry = $entries[$index];
                    $campaignName = $entry['campaignName'];
                    $adgroupName = $entry['adgroupName'];
                    $campaignId = $entry['campaignId'];
                    $adgroupId = $entry['adGroupId'];
                    if($response['code'] != 'DUPLICATE') {
                        $message = "Successfully added keyword $searchTerm to $campaignName > $adgroupName, match type: $matchType";
                        $messages[] = $message;
                        //create history entry for the action
                        SearchTermHistory::create([
                            'search_term' => $searchTerm,
                            'message' => $message,
                            'type' => 'negative',
                            'campaign_id' => $campaignId,
                            'ad_group_id' => $adgroupId,
                            'match_type' => $matchType
                        ]);
                    } else {
                        $messages[] = "Keyword $searchTerm was already added to $campaignName > $adgroupName, match type: $matchType";
                    }
                }
            }
        }catch (\Exception $e) {
            return response()->json([$e->getMessage()], 500);
        }
        return response()->json(['data' => $messages], 200);
    }

    /**
     * get search term options
     *
     * @return json response
     */
    public function getSearchtermOption(Request $request) {
        $result = [
            'default_campaign' => '',
            'default_adgroup' => '',
            'campaigns' => [],
            'combo_campaigns' => []
        ];
        $campaingName = $request->input('campaingName');
        $adGroupName = $request->input('adGroupName');
        try{
            //get default campaign
            $defaultCampaign = Campaigns::where([
                'name' => $campaingName,
            ])->first();
            //if campaign is auto and related get its manual counter part
            if($defaultCampaign->targeting_type == 'auto') {
                $campaignConnectionModel = CampaignConnection::where('auto_id', $defaultCampaign->id)->first();
                if($campaignConnectionModel) {
                    $campaignModel = Campaigns::find($campaignConnectionModel->manual_id);
                    if($campaignModel)
                        $defaultCampaign = $campaignModel;
                }
            }
            //get default ad group if there is default campaign
            if($defaultCampaign) {
                $result['default_campaign'] = $defaultCampaign->id;
                $defaultAdgroup = Adgroups::where([
                    'campaign_id' => $defaultCampaign->id,
                    'name' => $adGroupName,
                ])->first();
                if($defaultAdgroup)
                    $result['default_adgroup'] = $defaultAdgroup->id;
            }
            //get campaigns info
            $campaignModels = Campaigns::where('targeting_type', 'manual')->orderBy('name', 'ASC')->get(['id', 'name']);
            foreach($campaignModels as $campaignModel) {
                $campaignModel->adgroupsSimple;
            }
            $result['campaigns'] = $campaignModels;
            //get get campaigns and assciated campaigns info
            $comboCampaigns = [];
            $campaignModels = Campaigns::orderBy('name', 'ASC')->get();
            foreach($campaignModels as $campaignModel) {
                $comboCampaigns[$campaignModel->id] = $campaignModel;
            }
            $campaignConnections = CampaignConnection::all();
            foreach($campaignConnections as $campaignConnection) {
                $manualampaign = Campaigns::find($campaignConnection->manual_id);
                $autoCampaign = Campaigns::find($campaignConnection->auto_id);
                if($manualampaign && $autoCampaign) {
                    $manualCampaignName = $manualampaign->name;
                    $autoCampaignName = $autoCampaign->name;
                    $manualampaign->name = "| $manualCampaignName | AND | $autoCampaignName |";
                    $comboCampaigns[$manualampaign->id] = $manualampaign;
                    unset($comboCampaigns[$autoCampaign->id]);
                }
            }
            foreach($comboCampaigns as $comboCampaign) {
                $result['combo_campaigns'][] = $comboCampaign;
            }

        }catch (\Exception $e) {

            return response()->json(['errors' => [$e->getMessage()]], 422);
        }
        return response()->json(['data' => $result], 200);
    }

    /**
     * get Search term action history
     *
     * @param Request $request
     */
    public function getSearchtermHistory(Request $request) {
        $result = [
            'histories' => [],
            'count' => 0
        ];
        $skip = $request->input('skip');
        $rows = $request->input('rows');
        $searchTerm = $request->input('searchTerm');
        $campaignId = $request->input('campaignId');
        $adgroupId = $request->input('adgroupId');
        $sortOrder = (int) $request->input('sortOrder') > 0 ? 'asc' : 'desc';
        $criteria = array(
            'globalFilter' => $request->input('globalFilter'),
            'sortField' => $request->input('sortField'),
            'sortOrder' => $sortOrder
        );

        try{
            $model = new SearchTermHistory;
            if($campaignId) {
                $campaignId = explode(',', $campaignId);
                $model = $model ->whereIn('campaign_id', $campaignId);
            }
            if($searchTerm || $adgroupId) {
                $wheres = [];
                if($searchTerm)
                    $wheres['search_term'] = $searchTerm;
                if($adgroupId)
                    $wheres['ad_group_id'] = $adgroupId;
                $model = $model->where($wheres);
            }
            if($criteria['globalFilter'])
                $model = $model->where('search_term', 'LIKE', '%' . $criteria['globalFilter'] . '%');
            $result['count'] = $model->count();
            if($criteria['sortField'] && $criteria['sortOrder'])
                $model = $model->orderBy($criteria['sortField'], $criteria['sortOrder']);
            $result['histories'] = $model->offset($skip)->limit($rows)->get();
        }catch (\Exception $e) {
            return response()->json(['errors' => [$e->getMessage()]], 422);
        }
        return response()->json(['data' => $result], 200);
    }

    /**
     * upload search terms data
     *
     * @return json response
     */
    public function upload(Request $request) {

        $files = $this->parse_raw_http_request();
        $uploadedFileString = '';
        foreach($files as $name => $file) {
            $uploadedFileString = $file;
            break;
        }
        try{
            $name = str_replace('search-term-report-', '', $name);
            $name = explode('-', $name);
            if(!is_array($name) || count($name) < 3 || !is_numeric($name[0]) || !is_numeric($name[1]) || !is_numeric($name[2])) {
                throw new \Exception('Incorrect file name can not find file date!');
            }
            $beginDate = date("Y-m-d", strtotime($name[0].'-'.$name[1].'-'.$name[2]));
            $endDate = date("Y-m-d", strtotime($name[0].'-'.$name[1].'-'.$name[2] . ' + 2 months - 1 days'));
            Searchterm::truncate();
            $searchTerm = new Searchterm();
            $searchTerm->upload($uploadedFileString);
            $dateModel = $this->getDateModel();
            $dateModel->beginDate = $beginDate;
            $dateModel->endDate = $endDate;
            $dateModel->save();
        }catch (SearchtermUploadException $e){
            return response()->json(['errors' => [$e->getMessage()]], 422);
        }catch (\Exception $e) {
            return response()->json(['errors' => [$e->getMessage()]], 422);
        }
        return response()->json('{}', 200);
    }

    public function parse_raw_http_request()
    {
        $a_data = [];
        // read incoming data
        $input = file_get_contents('php://input');

        // grab multipart boundary from content type header
        preg_match('/boundary=(.*)$/', $_SERVER['CONTENT_TYPE'], $matches);
        $boundary = $matches[1];

        // split content by boundary and get rid of last -- element
        $a_blocks = preg_split("/-+$boundary/", $input);
        array_pop($a_blocks);

        // loop data blocks
        foreach ($a_blocks as $id => $block)
        {
            if (empty($block))
                continue;

            // you'll have to var_dump $block to understand this and maybe replace \n or \r with a visibile char

            // parse uploaded files
            if (strpos($block, 'application/octet-stream') !== FALSE)
            {
                // match "name", then everything after "stream" (optional) except for prepending newlines
                preg_match("/name=\"([^\"]*)\".*stream[\n|\r]+([^\n\r].*)?$/s", $block, $matches);
            }
            // parse all other fields
            else
            {
                // match "name" and optional value in between newline sequences
                preg_match('/name=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?\r$/s', $block, $matches);
            }
            $a_data[$matches[1]] = $matches[2];
        }
        return $a_data;
    }
}
