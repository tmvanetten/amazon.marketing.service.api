<?php
namespace App\Helpers;

require_once base_path('vendor/amzn/amazon-advertising-api-php/AmazonAdvertisingApi/Client.php');
use \Exception;
use AmazonAdvertisingApi\Client;

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

    /**
     * @param $keyword string keyword text
     * @param $campaignId bigInt campaign id
     * @param $adgroupId bigInt ad group id
     * @return AmazonAPI
     */
    public function doesPositiveKeyWordExist($keyword, $campaignId, $adgroupId, $matchType) {
        $response = $this->_client->listBiddableKeywords([
            "stateFilter" => "enabled",
            "keywordText" => $keyword,
            "campaignIdFilter" => $campaignId,
            "adGroupIdFilter" => $adgroupId,
            "matchTypeFilter" => $matchType
        ]);
    }

    private function requestException($methodName) {
        throw new AmazonAPIException("Cannot connect to Amazon API Method $methodName");
    }
}

class AmazonAPIException extends Exception{

}