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
    public static function getProductAds($reports_ids, $campaignId, $adGroupId, $criteria,  $skip = null, $rows = null){
        $query = DB::table('product_ads_report')->select(DB::raw('id, request_report_id, adId, adGroupId, campaignId, enabled, sku, asin, name,
            state, sum(clicks) clicks, sum(cost) cost, sum(impressions) impressions,
            sum(attributedSales1d) attributedSales1d, sum(attributedConversions1d) attributedConversions1d,
            sum(cost)/sum(clicks) as cpc, sum(cost)/sum(attributedSales1d)*100 as acos'));

        if(!empty($criteria['globalFilter'])) $query = $query->where('name', 'LIKE', '%' . $criteria['globalFilter'] . '%');

        $query = $query->where('campaignId', $campaignId)->where('adGroupId', $adGroupId)->whereIn('request_report_id', $reports_ids)->groupBy('adId');

        if($criteria['sortField'] && $criteria['sortOrder'])
            $query = $query->orderBy($criteria['sortField'], $criteria['sortOrder']);

        if(!is_null($skip) || !is_null($rows)) return $query->offset($skip)->limit($rows)->get();
        return $query->get();
    }
}
