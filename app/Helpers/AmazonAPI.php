<?php
namespace App\Helpers;

require_once base_path('vendor/amzn/amazon-advertising-api-php/AmazonAdvertisingApi/Client.php');
use \Exception;
use AmazonAdvertisingApi\Client;
use App\Model\Keywords;
use App\Model\Adgroups;

class AmazonAPI {
    //amazon advertise API client
    protected $_client;

    public function __construct() {
        $config = [
            "clientId" => env('AMAZN_ID'),
            "clientSecret" => env('AMAZN_SECRET'),
            "refreshToken" => env('AMAZN_TOKEN'),
            "region" => env('AMAZN_REGION'),
            "sandbox" => env('AMAZN_SANDBOX')
        ];
        $this->_client = new Client($config);
        $request = $this->_client->getProfiles();
        if ($request['success'] != 1) {
            throw new \Exception('Error: no profile found.');
        }
        $data = $request['response'];
        $profileData = json_decode($data);
        foreach($profileData as $profile){
            if($profile->countryCode = "US") {
                $this->_client->profileId = $profile->profileId;
                break;
            }
        }
    }

    /**
     * @param $keyword string keyword data
     * @return AmazonAPIResponse
     */
    public function createPositiveKeyword($keyword) {
        $responses = $this->_client->createBiddableKeywords([$keyword]);
        if(!$responses['success'])
            $this->requestException(__FUNCTION__);
        return json_decode($responses['response'], true);
    }

    /**
     * @param $keywords array keyword data
     * @return AmazonAPI
     */
    public function createNegativeKeywords($keywords) {
        $responses = $this->_client->createNegativeKeywords($keywords);
        if(!$responses['success'])
            $this->requestException(__FUNCTION__);
        return json_decode($responses['response'], true);
    }

    public function getAndCreateBiddableKeyword($id) {
        $responses = $this->_client->getBiddableKeyword($id);
        if(!$responses['success'])
            $this->requestException(__FUNCTION__);
        $keyword = json_decode($responses['response'], true);
        $data = [
            'id' => $keyword['keywordId'],
            'ad_group_id' => $keyword['adGroupId'],
            'campaign_id' => $keyword['campaignId'],
            'keyword_text' => $keyword['keywordText'],
            'match_type' => $keyword['matchType'],
            'bid' => isset($keyword['bid']) ? $keyword['bid'] : 0,
            'state' => $keyword['state']
        ];
        $keyword = Keywords::find($data['id']);
        if($keyword) {
            $this->_updateModel($keyword, $data)->save();
        } else {
            Keywords::create($data);
        }
        return $this;
    }

    public function getAndCreateNKeyword($id) {
        $responses = $this->_client->getNegativeKeyword($id);
        if(!$responses['success'])
            $this->requestException(__FUNCTION__);
        $keyword = json_decode($responses['response'], true);
        $nkeyword = NegativeKeywordsReport::where('campaignId', $keyword['campaignId'])
            ->where('adGroupId', $keyword['adGroupId'])
            ->where('keywordId', $keyword['keywordId'])
            ->first();
        return $this;
    }

    public function bidOnAdgroup($adgroupId, $bid) {
        $adgroupId = (int) $adgroupId;
        $responses = $this->_client->updateAdGroups([
            [
                "adGroupId" => $adgroupId,
                "defaultBid" => $bid
            ]
        ]);
        if(!$responses['success'])
            $this->requestException(__FUNCTION__);
        return json_decode($responses['response'], true);
    }

    public function bidOnKeyword($keywordId, $bid) {
        $keywordId = (int) $keywordId;
        $responses = $this->_client->updateBiddableKeywords([
            [
                "keywordId" => $keywordId,
                "bid" => $bid
            ]
        ]);
        if(!$responses['success'])
            $this->requestException(__FUNCTION__);
        return json_decode($responses['response'], true);
    }

    private function requestException($methodName) {
        throw new AmazonAPIException("Cannot connect to Amazon API Method $methodName");
    }

    protected function _updateModel($model, $data){
        foreach ($data as $key=>$value){
            $model->$key = $value;
        }
        return $model;
    }
}

class AmazonAPIException extends Exception{

}