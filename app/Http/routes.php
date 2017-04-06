<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$app->get('/', function () use ($app) {
    return $app->version();
});

$app->group(['prefix' => 'api'], function () use ($app) {
    //route for Capmaigns
	$app->post('campaigns', [
        'as' => 'campaigns', 'uses' => 'App\Http\Controllers\CampaignsController@index'
    ]);
    //route to get campaigns list
    $app->get('listCampaigns', [
        'as' => 'listCampaigns', 'uses' => 'App\Http\Controllers\CampaignsController@listCampaigns'
    ]);
    //route to get connected campaigns
    $app->get('connectedCampaigns', [
        'as' => 'connectedCampaigns', 'uses' => 'App\Http\Controllers\CampaignsController@getConnectedCampaigns'
    ]);
    //route to connect campaigns manually
    $app->post('connectCampaignsManual', [
        'as' => 'connectCampaignsManual', 'uses' => 'App\Http\Controllers\CampaignsController@manuallyConnectCampaigns'
    ]);
    //route to connect campaigns automatically
    $app->get('connectCampaignsAuto', [
        'as' => 'connectCampaignsAuto', 'uses' => 'App\Http\Controllers\CampaignsController@automaticConnect'
    ]);
    //route for Campaign
    $app->post('campaign', [
        'as' => 'campaign', 'uses' => 'App\Http\Controllers\CampaignsController@getCampaignById'
    ]);
    //route for Product Ads  // `/${id}/adGroup/${gid}/selectedDate/${selectedDate}`;
    $app->get('productAds', [
        'as' => 'productAds', 'uses' => 'App\Http\Controllers\CampaignsController@getProductAds'
    ]);
    //route for Keywords  // `/${id}/adGroup/${gid}/selectedDate/${selectedDate}`;
    $app->get('keyWords', [
        'as' => 'keyWords', 'uses' => 'App\Http\Controllers\CampaignsController@getKeywords'
    ]);
    //route for Negative Keywords  // `/${id}/adGroup/${gid}/selectedDate/${selectedDate}`;
    $app->get('negativeKeyWords', [
        'as' => 'negativeKeyWords', 'uses' => 'App\Http\Controllers\CampaignsController@getNegativeKeywords'
    ]);
    //route for switch function;
    $app->post('enableDisable', [
        'as' => 'enableDisable', 'uses' => 'App\Http\Controllers\CampaignsController@enableDisable'
    ]);
    //route to get dates array
    $app->get('dates', [
        'as' => 'dates', 'uses' => 'App\Http\Controllers\InfoController@getDates'
    ]);
    //route to get dates array
    $app->get('strategy', [
        'as' => 'strategy', 'uses' => 'App\Http\Controllers\StrategyController@getStrategy'
    ]);
    //route to get dates array
    $app->post('strategy', [
        'as' => 'strategy', 'uses' => 'App\Http\Controllers\StrategyController@saveStrategy'
    ]);
    //route to get dates array
    $app->post('strategyhistory', [
        'as' => 'strategyhistory', 'uses' => 'App\Http\Controllers\StrategyController@getStrategyHistory'
    ]);
    //route to upload search term
    $app->post('uploadsearchterm', [
        'as' => 'uploadsearchterm', 'uses' => 'App\Http\Controllers\SearchtermController@upload'
    ]);
    //route to get search term
    $app->post('searchterm', [
        'as' => 'searchterm', 'uses' => 'App\Http\Controllers\SearchtermController@get'
    ]);
    $app->get('searchtermoption', [
        'as' => 'searchtermoption', 'uses' => 'App\Http\Controllers\SearchtermController@getSearchtermOption'
    ]);
    //route to create new positive keyword
    $app->post('positivekeyword', [
        'as' => 'positivekeyword', 'uses' => 'App\Http\Controllers\SearchtermController@createPositiveKeyword'
    ]);
    //route to create new negative keyword
    $app->post('negativekeyword', [
        'as' => 'negativekeyword', 'uses' => 'App\Http\Controllers\SearchtermController@createNegativeKeyword'
    ]);
    //route to get search term history
    $app->get('searchtermhistory', [
        'as' => 'searchtermoption', 'uses' => 'App\Http\Controllers\SearchtermController@getSearchtermHistory'
    ]);
    $app->get('campaignconnection', [
        'as' => 'campaignconnection', 'uses' => 'App\Http\Controllers\CampaignsController@getCampaignConnection'
    ]);
    $app->get('keywordhistory', [
        'as' => 'keywordhistory', 'uses' => 'App\Http\Controllers\CampaignsController@getKeywordHistory'
    ]);
});