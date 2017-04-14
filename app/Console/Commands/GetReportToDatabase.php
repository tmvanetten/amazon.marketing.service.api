<?php

namespace App\Console\Commands;

require_once base_path('vendor/amzn/amazon-advertising-api-php/AmazonAdvertisingApi/Client.php');

use Illuminate\Console\Command;
use AmazonAdvertisingApi\Client;
use App\RequestReportAPI;
use App\AdGroupsReport;
use App\ProductAdsReport;
use App\KeywordsReport;
use App\NegativeKeywordsReport;
use App\CampaignReport;
use App\ReportItems;
use App\Model\Campaigns;
use App\Model\Adgroups;
use App\Model\ProductAds;
use App\Model\Keywords;

class GetReportToDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'amazon:get_report_to_database';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get result of Report via Amazon API & save to database.';

    protected $client;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $dates = [
            'yesterday' => date("Ymd", time() - 60 * 60 * 24),
            'pastTwoDays' => date("Ymd", time() - 60 * 60 * 48),
            'pastThreeDays' => date("Ymd", time() - 60 * 60 * 72),
            'pastFourDays' => date("Ymd", time() - 60 * 60 * 96),
            'pastFiveDays' => date("Ymd", time() - 60 * 60 * 120),
            'pastSixDays' => date("Ymd", time() - 60 * 60 * 144),
            'pastSevenDays' => date("Ymd", time() - 60 * 60 * 168)
        ];
        $config = array(
            "clientId" => env('AMAZN_ID'),
            "clientSecret" => env('AMAZN_SECRET'),
            "refreshToken" => env('AMAZN_TOKEN'),
            "region" => env('AMAZN_REGION'),
            "sandbox" => env('AMAZN_SANDBOX'),
        );

        $client = new Client($config);
        $this->client = $client;
        $this->_downloadInfo();
        $totalCompleted = 0;
        $day = 1;
        while($this->_getPastDayDate($day) >= $this->_getPastDayDate(7)){
            $items = RequestReportAPI::where('amazn_report_date', $this->_getPastDayDate($day))
                ->where('amazn_status', 'IN_PROGRESS')
                ->get();
            foreach ($items as $item) {
                $client->profileId = $item->amazn_profile_id;
                $request = $client->getReport($item->amazn_report_id);
                $data = json_decode($request['response']);
                if ($request['success'] != 1) {
                    $this->error('Error: Not generate report '.$item->amazn_report_id);
                } else {
                    $item->amazn_status = 'SUCCESS';
                    $item->save();
                    foreach($data as $dataItem) {
                        $dataItem = (array) $dataItem;
                        if ($item->type == 'campaigns') {
                            if(isset($dataItem['campaignId'])){
                                $requestCampaignData = $client->getCampaign($dataItem['campaignId']);
                                if($requestCampaignData['success']) {
                                    $campaignData = json_decode($requestCampaignData['response']);
                                    $campaignData = (array) $campaignData;
                                    $result = $this->prepareData($campaignData, $dataItem);
                                    $campaign = CampaignReport::where('campaignId', $result['campaignId'])
                                        ->where('request_report_id', $item->id)
                                        ->first();
                                    if(is_null($campaign)){
                                        $campaign = CampaignReport::create($result);
                                    }else{
                                        $campaign = $this->_updateModel($campaign, $result);
                                    }
                                    $campaign->request_report_id = $item->id;
                                    $campaign->save();
                                    $totalCompleted++;
                                }
                            }
                        } else if ($item->type == 'adGroups') {
                            if(isset($dataItem['adGroupId'])) {
                                $requestAdGroupData = $client->getAdGroup($dataItem['adGroupId']);
                                if ($requestAdGroupData['success']) {
                                    $adGroupData = json_decode($requestAdGroupData['response']);
                                    $adGroupData = (array)$adGroupData;
                                    $result = $this->prepareData($adGroupData, $dataItem);
                                    $adGroup = AdGroupsReport::where('campaignId', $result['campaignId'])
                                        ->where('adGroupId', $result['adGroupId'])
                                        ->where('request_report_id', $item->id)
                                        ->first();
                                    if(is_null($adGroup)){
                                        $adGroup = AdGroupsReport::create($result);
                                    }else{
                                        if(isset($result['defaultBid']))
                                            unset($result['defaultBid']);
                                        $adGroup = $this->_updateModel($adGroup, $result);
                                    }
                                    $adGroup->request_report_id = $item->id;
                                    $adGroup->save();
                                    if (!NegativeKeywordsReport::where('ad_group_id', $adGroup->id)->first())
                                        $this->_getNegativeKeywords($client, $adGroup->id,
                                            [
                                                'campaignIdFilter'=>$adGroup->campaignId,
                                                'adGroupIdFilter'=>$adGroup->adGroupId
                                            ]
                                        );
                                    $totalCompleted++;
                                }
                            }
                        } else if ($item->type == 'keywords') {
                            if(isset($dataItem['keywordId'])) {
                                $requestKeywordData = $client->getBiddableKeyword($dataItem['keywordId']);
                                if ($requestKeywordData['success']) {
                                    $keywordData = json_decode($requestKeywordData['response']);
                                    $keywordData = (array)$keywordData;
                                    $result = $this->prepareData($keywordData, $dataItem);
                                    $keyword = KeywordsReport::where('campaignId', $result['campaignId'])
                                        ->where('adGroupId', $result['adGroupId'])
                                        ->where('keywordId', $result['keywordId'])
                                        ->where('request_report_id', $item->id)
                                        ->first();
                                    if(is_null($keyword)){
                                        $keyword = KeywordsReport::create($result);
                                    }else{
                                        if(isset($result['bid']))
                                            unset($result['bid']);
                                        $keyword = $this->_updateModel($keyword, $result);
                                    }
                                    $keyword->request_report_id = $item->id;
                                    $keyword->save();
                                    $totalCompleted++;
                                }
                            }
                        } else if ($item->type == 'productAds') {
                            if(isset($dataItem['adId'])){
                                $requestAdData = $client->getProductAd($dataItem['adId']);
                                //var_dump($requestAdData);
                                if($requestAdData['success']) {
                                    $adData = json_decode($requestAdData['response']);
                                    $adData = (array) $adData;
                                    $result = $this->prepareData($adData, $dataItem);
                                    $sku_arr = explode(" ", $result['sku']);
                                    $name = '';
                                    foreach($sku_arr as $index => $value){
                                        if ($index === 0) continue;
                                        $stop = false;
                                        switch($value){
                                            case 'CA':
                                                $stop = true;
                                                break;
                                            case 'FBA':
                                                $stop = true;
                                                break;
                                            case 'Fba':
                                                $stop = true;
                                                break;
                                            case 'fba-CA':
                                                $stop = true;
                                                break;
                                            case 'FBA-CA':
                                                $stop = true;
                                                break;
                                            case 'new-CA':
                                                $stop = true;
                                                break;
                                            default:
                                                $name .= $value . ' ';
                                        }
                                        if($stop) break;
                                    }
                                    $result['name'] = $name;
                                    $ad = ProductAdsReport::where('campaignId', $result['campaignId'])
                                        ->where('adGroupId', $result['adGroupId'])
                                        ->where('adId', $result['adId'])
                                        ->where('request_report_id', $item->id)
                                        ->first();
                                    if(is_null($ad)){
                                        $ad = ProductAdsReport::create($result);
                                    }else{
                                        $ad = $this->_updateModel($ad, $result);
                                    }
                                    $ad->request_report_id = $item->id;
                                    $ad->save();
                                    $totalCompleted++;
                                }
                            }
                        }
                    }
                }
            }
            $day++;
        }

        if ($totalCompleted) {
            $this->info($totalCompleted . ' report item generated & save to database Date:' . date("Ymd h:i:s A"));
        }
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

    protected function _downloadInfo() {
        $request = $this->client->getProfiles();
        if ($request['success'] != 1) {
            return 'Error: Not any profile found.';
        }
        $data = $request['response'];
        $profileData = json_decode($data);
        foreach($profileData as $profile){
            if($profile->countryCode = "US") {
                $this->client->profileId = $profile->profileId;
                break;
            }
        }
        $this->_downloadCampaigns();
        $this->_downloadAdgroups();
        $this->_downloadProductAds();
        $this->_downloadKeyword();
    }

    protected function _downloadCampaigns() {
        //request campaigns data from amazon
        $request = $this->client->listCampaigns(array("stateFilter" => "enabled"));
        if(!$request['success'])
            return 'Error: Listing Campaigns.';
        $data = $request['response'];
        $campaigns = json_decode($data, true);
        //retrive campaigns data from db
        $campaignsInDb = Campaigns::all();
        $campaignsIdsInDb = [];
        $campaingModels = [];
        foreach($campaignsInDb as $campaignInDb) {
            $campaignsIdsInDb[] = $campaignInDb->id;
            $campaingModels[$campaignInDb->id] = $campaignInDb;
        }

        //create or update campaigns data in db
        foreach($campaigns as $campaign) {
            $data = [
                'id' => $campaign['campaignId'],
                'name' => $campaign['name'],
                'campaign_type' => $campaign['campaignType'],
                'targeting_type' => $campaign['targetingType'],
                'daily_budget' => $campaign['dailyBudget'],
                'state' => $campaign['state']
            ];
            if(in_array($data['id'], $campaignsIdsInDb)) {
                if(isset($campaingModels[$data['id']])) {
                    $this->_updateModel($campaingModels[$data['id']], $data)->save();
                }
                unset($campaingModels[$data['id']]);
            } else {
                Campaigns::create($data);
            }
        }

        //delete campaigns data that are no longer used
        foreach($campaingModels as $campaingModel) {
            $campaingModel->delete();
        }
        return $this;
    }

    protected function _downloadAdgroups() {
        $request = $this->client->listAdGroups(array("stateFilter" => "enabled"));
        if(!$request['success'])
            return 'Error: Listing Ad Groups.';
        $data = $request['response'];
        $adgroups = json_decode($data, true);
        //retrive adgroups data from db
        $adgroupsInDb = Adgroups::all();
        $adgroupsIdsInDb = [];
        $adgroupModels = [];
        foreach($adgroupsInDb as $adgroupInDb) {
            $adgroupsIdsInDb[] = $adgroupInDb->id;
            $adgroupModels[$adgroupInDb->id] = $adgroupInDb;
        }

        //create or update adgroups data in db
        foreach($adgroups as $adgroup) {
            $data = [
                'id' => $adgroup['adGroupId'],
                'campaign_id' => $adgroup['campaignId'],
                'name' => $adgroup['name'],
                'default_bid' => $adgroup['defaultBid'],
                'state' => $adgroup['state']
            ];
            try{
                if(in_array($data['id'], $adgroupsIdsInDb)) {
                    if(isset($adgroupModels[$data['id']])) {
                        $this->_updateModel($adgroupModels[$data['id']], $data)->save();
                    }
                    unset($adgroupModels[$data['id']]);
                } else {
                    Adgroups::create($data);
                }
            }catch (\Exception $e) {
                //ignore table contrain foregin key exception
            }
        }

        //delete adgroups data that are no longer used
        foreach($adgroupModels as $adgroupModel) {
            $adgroupModel->delete();
        }
        return $this;
    }

    protected function _downloadProductAds() {
        $campaignsInDb = Campaigns::all();
        //retrive product Ads data from db
        $productAdsInDb = ProductAds::all();
        $productAdsIdsInDb = [];
        $productAdModels = [];
        foreach($productAdsInDb as $productAdInDb) {
            $productAdsIdsInDb[] = $productAdInDb->id;
            $productAdModels[$productAdInDb->id] = $productAdInDb;
        }
        foreach($campaignsInDb as $campaign) {
            $request = $this->client->listProductAds(array("campaignIdFilter" => $campaign->id, "stateFilter" => "enabled"));
            if(!$request['success'])
                return 'Error: Listing Product Ads.';
            $data = $request['response'];
            $productAds = json_decode($data, true);
            //create or update product Ads data in db
            foreach($productAds as $productAd) {
                $data = [
                    'id' => $productAd['adId'],
                    'ad_group_id' => $productAd['adGroupId'],
                    'campaign_id' => $productAd['campaignId'],
                    'sku' => $productAd['sku'],
                    'asin' => isset($productAd['asin']) ? $productAd['asin'] : '',
                    'state' => $productAd['state']
                ];
                try{
                    if(in_array($data['id'], $productAdsIdsInDb)) {
                        if(isset($productAdModels[$data['id']])) {
                            $this->_updateModel($productAdModels[$data['id']], $data)->save();
                        }
                        unset($productAdModels[$data['id']]);
                    } else {
                        ProductAds::create($data);
                    }
                }catch (\Exception $e) {
                    //ignore table contrain foregin key exception
                }
            }
        }
        //delete product Ads data that are no longer used
        foreach($productAdModels as $productAdModel) {
            $productAdModel->delete();
        }

        return $this;
    }

    protected function _downloadKeyword() {
        $request = $this->client->listBiddableKeywords(array("stateFilter" => "enabled"));
        if(!$request['success'])
            return 'Error: Listing keywords.';
        $data = $request['response'];
        $keywords = json_decode($data, true);
        //retrive keywords data from db
        $keywordsInDb = Keywords::all();
        $keywordsIdsInDb = [];
        $keywordModels = [];
        foreach($keywordsInDb as $keywordInDb) {
            $keywordsIdsInDb[] = $keywordInDb->id;
            $keywordModels[$keywordInDb->id] = $keywordInDb;
        }

        //create or update keywords data in db
        foreach($keywords as $keyword) {
            $data = [
                'id' => $keyword['keywordId'],
                'ad_group_id' => $keyword['adGroupId'],
                'campaign_id' => $keyword['campaignId'],
                'keyword_text' => $keyword['keywordText'],
                'match_type' => $keyword['matchType'],
                'bid' => isset($keyword['bid']) ? $keyword['bid'] : 0,
                'state' => $keyword['state']
            ];
            try{
                if(in_array($data['id'], $keywordsIdsInDb)) {
                    if(isset($keywordModels[$data['id']])) {
                        $this->_updateModel($keywordModels[$data['id']], $data)->save();
                    }
                    unset($keywordModels[$data['id']]);
                } else {
                    Keywords::create($data);
                }
            }catch (\Exception $e) {
                //ignore table contrain foregin key exception
            }
        }

        //delete keywords data that are no longer used
        foreach($keywordModels as $adgroupModel) {
            $adgroupModel->delete();
        }
        return $this;
    }

    /**
     * Prepare data for mass saving to model.
     *
     * @return mixed
     */
    public function prepareData($requestedData, $dataFromReport)
    {
        $requestedData['cost'] = !empty($dataFromReport['cost']) ? $dataFromReport['cost'] : '';
        $requestedData['clicks'] = !empty($dataFromReport['clicks']) ? $dataFromReport['clicks'] : '';
        $requestedData['impressions'] = !empty($dataFromReport['impressions']) ? $dataFromReport['impressions'] : '';
        $requestedData['attributedConversions1dSameSKU'] = !empty($dataFromReport['attributedConversions30dSameSKU']) ? $dataFromReport['attributedConversions30dSameSKU'] : '';
        $requestedData['attributedSales1d'] = !empty($dataFromReport['attributedSales30d']) ? $dataFromReport['attributedSales30d'] : '';
        $requestedData['attributedConversions1d'] = !empty($dataFromReport['attributedConversions30d']) ? $dataFromReport['attributedConversions30d'] : '';
        $requestedData['attributedSales1dSameSKU'] = !empty($dataFromReport['attributedSales30dSameSKU']) ? $dataFromReport['attributedSales30dSameSKU'] : '';
        return $requestedData;
    }

    /**
     * Get Negative keywords at adgroup level.
     *
     * @param $client Client
     * @param $filterArray array
     * @return array
     */
    protected function _getNegativeKeywords($client, $adGroupId, $filterArray)
    {
        $nkeywords = $client->listNegativeKeywords($filterArray);
        $keywordData = json_decode($nkeywords['response']);
        $toSave  = (array)$keywordData;
        if(!empty($toSave)){
            foreach($toSave as $item){
                $itemData = (array)$item;
                $nkeyword = NegativeKeywordsReport::where('campaignId', $itemData['campaignId'])
                    ->where('adGroupId', $itemData['adGroupId'])
                    ->where('keywordId', $itemData['keywordId'])
                    ->where('ad_group_id', $adGroupId)
                    ->first();
                if(is_null($nkeyword)){
                    $nkeyword = NegativeKeywordsReport::create($itemData);
                }else {
                    $nkeyword = $this->_updateModel($nkeyword, $itemData);
                }
                $nkeyword->ad_group_id = $adGroupId;
                $nkeyword->save();
            }
            return true;
        }
        return false;
    }

    /**
     * Mass assign to model.
     *
     * @param $model Eloquent
     * @param $data array
     * @return Eloquent
     */
    protected function _updateModel($model, $data){
        foreach ($data as $key=>$value){
            $model->$key = $value;
        }
        return $model;
    }
}
