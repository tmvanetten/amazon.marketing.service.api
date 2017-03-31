<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use DB;

class ProductAds extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'campaign_id', 'ad_group_id', 'sku', 'state', 'created_at', 'updated_at', 'asin'
    ];

    protected $table = 'productads';
    /**
     * Get campaigns by date.
     *
     * @var $selectedDate string
     * @var $skip integer
     * @var $rows integer
     * @return array
     */
    public static function getProductAds($campaignId, $adgroupId, $criteria, $beginData, $endDate, $skip = null, $rows = null){
        $query = DB::table('productads')->select(DB::raw('
            productads.id,
            productads.id as adId,
            productads.ad_group_id as adGroupId,
            productads.campaign_id as campaignId,
            productads.sku,
            productads.asin,
            CASE WHEN product_ads_report.name IS NULL THEN productads.sku ELSE product_ads_report.name END as name ,
            productads.state,
            sum(product_ads_report.clicks) clicks,
            sum(product_ads_report.cost) cost,
            sum(product_ads_report.impressions) impressions,
            sum(product_ads_report.attributedSales1d) attributedSales1d,
            sum(product_ads_report.attributedConversions1d) attributedConversions1d,
            sum(product_ads_report.cost)/sum(product_ads_report.clicks) as cpc,
            sum(product_ads_report.cost)/sum(product_ads_report.attributedSales1d)*100 as acos'));

        $query->LeftJoin('reqest_report_api', function ($join) use ($beginData, &$endDate){
            $join->where('reqest_report_api.type', '=', 'productAds')
                ->where('reqest_report_api.amazn_report_date', '>=', $beginData)
                ->where('reqest_report_api.amazn_report_date', '<=', $endDate);
        });
        $query->LeftJoin('product_ads_report', function ($join) use ($beginData, &$endDate){
            $join->on( 'productads.id', '=', 'product_ads_report.adId')
                ->on('product_ads_report.request_report_id', '=', 'reqest_report_api.id');
        });

        $query = $query->groupBy('productads.id');
        $query = $query->where('productads.campaign_id', $campaignId);
        $query = $query->where('productads.ad_group_id', $adgroupId);

        if(!empty($criteria['globalFilter']))
            $query = $query->where('product_ads_report.name', 'LIKE', '%' . $criteria['globalFilter'] . '%');

        if($criteria['sortField'] && $criteria['sortOrder'])
            $query = $query->orderBy($criteria['sortField'], $criteria['sortOrder']);

        if(!is_null($skip) || !is_null($rows)) return $query->offset($skip)->limit($rows)->get();
        //$results = $query->toSql();
        //var_dump($results);
        return $query->get();
    }

    public static function getProductAdsCount($campaignId, $adgroupId, $criteria) {
        $query = DB::table('productads');
        $query = $query->where('productads.campaign_id', $campaignId);
        $query = $query->where('productads.ad_group_id', $adgroupId);
        if(!empty($criteria['globalFilter']))
            $query = $query->where('product_ads_report.name', 'LIKE', '%' . $criteria['globalFilter'] . '%');

        //$query = $query->groupBy('c.id');
        return $query->count();
    }

}
