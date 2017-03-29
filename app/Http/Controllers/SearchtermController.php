<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use Illuminate\Http\Request;
use App\Model\Searchterm;
use App\Model\Campaigns;
use App\Model\Adgroups;
use App\Model\CampaignConnection;
use App\Model\SearchtermUploadException;

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

    /**
     * get search terms data
     *
     * @return json response
     */
    public function get(Request $request) {
        $result = [
          'searchterms' => [],
          'count' => 0
        ];
        $skip = $request->input('skip');
        $rows = $request->input('rows');
        $campaingName = $request->input('campaingName');
        $adGroupName = $request->input('adGroupName');
        $keyWordName = $request->input('keyWordName');
        $matchType = $request->input('matchType');
        try{
            if($campaingName || $adGroupName || $keyWordName) {
                $wheres = [];
                if($campaingName)
                    $wheres['campaign_name'] = $campaingName;
                if($adGroupName)
                    $wheres['adgroup_name'] = $adGroupName;
                if($keyWordName)
                    $wheres['keyworod'] = $keyWordName;
                if($matchType)
                    $wheres['match_type'] = $matchType;
                $result['searchterms'] = Searchterm::where($wheres)->offset($skip)->limit($rows)->get();
                $result['count'] = Searchterm::where($wheres)->count();
            } else {
                $result['searchterms'] = Searchterm::take($rows)->offset($skip)->get();
                $result['count'] = count(Searchterm::all());
            }
        }catch (\Exception $e) {
            return response()->json(['errors' => [$e->getMessage()]], 422);
        }
        return response()->json(['data' => $result], 200);
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
            Searchterm::truncate();
            $searchTerm = new Searchterm();
            $searchTerm->upload($uploadedFileString);
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
