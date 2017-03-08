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

$app->get('test', [
    'as' => 'test', 'uses' => 'ExampleController@showDb'
]);
/*
$app->get('campaigns', [
    'as' => 'campaigns', 'uses' => 'CampaignsController@index'
]); 
*/
$app->group(['prefix' => 'api'], function () use ($app) {
    //route for Capmaigns
	$app->get('campaigns/{date}', [
        'as' => 'campaigns', 'uses' => 'App\Http\Controllers\CampaignsController@index'
    ]);
    //route for Campaign
    $app->get('campaign/{campaignId}/selectedDate/{selectedDate}', [
        'as' => 'campaign', 'uses' => 'App\Http\Controllers\CampaignsController@getCampaignById'
    ]);
    //route for Product Ads  // `/${id}/adGroup/${gid}/selectedDate/${selectedDate}`;
    $app->get('campaign/{campaignId}/adGroup/{adgroupId}/selectedDate/{selectedDate}', [
        'as' => 'campaign', 'uses' => 'App\Http\Controllers\CampaignsController@getProductAd'
    ]);
    //route for Keywords  // `/${id}/adGroup/${gid}/selectedDate/${selectedDate}`;
    $app->get('keywords/{campaignId}/adGroup/{adgroupId}/selectedDate/{selectedDate}', [
        'as' => 'keywords', 'uses' => 'App\Http\Controllers\CampaignsController@getKeywords'
    ]);
    //route for Negative Keywords  // `/${id}/adGroup/${gid}/selectedDate/${selectedDate}`;
    $app->get('nkeywords/{campaignId}/adGroup/{adgroupId}/selectedDate/{selectedDate}', [
        'as' => 'nkeywords', 'uses' => 'App\Http\Controllers\CampaignsController@getNKeywords'
    ]);
    //route to get dates array
    $app->get('dates', [
        'as' => 'dates', 'uses' => 'App\Http\Controllers\InfoController@getDates'
    ]);
});