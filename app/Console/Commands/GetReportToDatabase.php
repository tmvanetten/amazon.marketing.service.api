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
        $this->info(' ');
        $this->info('--- Start Command ---');
        $this->info(' ');
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
        $totalCompleted = 0;
        foreach($dates as $date){
            $items = RequestReportAPI::where('amazn_report_date', $date)
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
                                    var_dump($requestCampaignData);
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
                                var_dump($requestAdGroupData);
                                if ($requestAdGroupData['success']) {
                                    $adGroupData = json_decode($requestAdGroupData['response']);
                                    $adGroupData = (array)$adGroupData;
                                    //var_dump($requestAdGroupData);
                                    $result = $this->prepareData($adGroupData, $dataItem);
                                    $adGroup = AdGroupsReport::where('campaignId', $result['campaignId'])
                                        ->where('adGroupId', $result['adGroupId'])
                                        ->where('request_report_id', $item->id)
                                        ->first();
                                    if(is_null($adGroup)){
                                        $adGroup = AdGroupsReport::create($result);
                                    }else{
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
                                var_dump($requestKeywordData);
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
                                var_dump($requestAdData);
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
        }

        if ($totalCompleted) {
            $this->info(' ');
            $this->info($totalCompleted . ' report item generated & save to database ');
            $this->info(' ');
        }
        $this->info(' ');
        $this->info('--- Finish Command ---');
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
        $requestedData['attributedConversions1dSameSKU'] = !empty($dataFromReport['attributedConversions1dSameSKU']) ? $dataFromReport['attributedConversions1dSameSKU'] : '';
        $requestedData['attributedSales1d'] = !empty($dataFromReport['attributedSales1d']) ? $dataFromReport['attributedSales1d'] : '';
        $requestedData['attributedConversions1d'] = !empty($dataFromReport['attributedConversions1d']) ? $dataFromReport['attributedConversions1d'] : '';
        $requestedData['attributedSales1dSameSKU'] = !empty($dataFromReport['attributedSales1dSameSKU']) ? $dataFromReport['attributedSales1dSameSKU'] : '';
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
