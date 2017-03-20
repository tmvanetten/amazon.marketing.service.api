<?php

namespace App;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class ProductAdsReport extends Model
{
    protected $table = 'product_ads_report';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'request_report_id',
        'adId',
        'campaignId',
        'adGroupId',
        'enabled',
        'name',
        'sku',
        'asin',
        'state',
        'clicks',
        'cost',
        'impressions',
        'attributedConversions1dSameSKU',
        'attributedSales1d',
        'attributedConversions1d',
        'attributedSales1dSameSKU'
    ];

    protected $hidden = [
        'update_at', 'created_at',
    ];

    /**
     * Get adgroups by date.
     *
     * @var $selectedDate string
     * @var $skip integer
     * @var $rows integer
     * @return array
     */
    public static function getProductAds($reports_ids, $campaignId, $adGroupId,  $skip = null, $rows = null){
        $query = DB::table('product_ads_report')->select(DB::raw('id, request_report_id, adId, adGroupId, campaignId, enabled, sku, asin, name,
         state, avg(clicks) clicks, avg(cost) cost, avg(impressions) impressions, avg(attributedConversions1dSameSKU) attributedConversions1dSameSKU,
            avg(attributedSales1d) attributedSales1d, avg(attributedConversions1d) attributedConversions1d, avg(attributedSales1dSameSKU) attributedSales1dSameSKU'))
            ->where('campaignId', $campaignId)->where('adGroupId', $adGroupId)->whereIn('request_report_id', $reports_ids)->groupBy('adId');

        if(!is_null($skip) || !is_null($rows)) return $query->offset($skip)->limit($rows)->get();
        return $query->get();
    }
}
