<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use DB;

class Keywords extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'campaign_id', 'ad_group_id', 'keyword_text', 'match_type', 'state', 'bid', 'created_at', 'updated_at'
    ];

    protected $table = 'keywords';

    /**
     * Get campaigns by date.
     *
     * @var $selectedDate string
     * @var $skip integer
     * @var $rows integer
     * @return array
     */
    public static function getKeywords($campaignId, $adgroupId, $criteria, $beginData, $endDate, $skip = null, $rows = null){

        $query = DB::table('keywords_report')->select(DB::raw('id, request_report_id, keywordId, adGroupId, campaignId, enabled, keywordText, matchType,
         state, bid, sum(clicks) clicks, sum(cost) cost, avg(impressions) impressions, sum(attributedConversions1dSameSKU) attributedConversions1dSameSKU,
            sum(attributedSales1d) attributedSales1d, sum(attributedConversions1d) attributedConversions1d, sum(attributedSales1dSameSKU) attributedSales1dSameSKU,
            sum(cost)/sum(clicks) as cpc, sum(cost)/sum(attributedSales1d)*100 as acos'));

        $query = DB::table('keywords')->select(DB::raw('
            keywords.id,
            keywords.id as keywordId,
            keywords.ad_group_id as adGroupId,
            keywords.campaign_id as campaignId,
            keywords.keyword_text as keywordText,
            keywords.match_type as matchType,
            keywords.state,
            keywords.bid,
            sum(keywords_report.clicks) clicks,
            sum(keywords_report.cost) cost,
            avg(keywords_report.impressions) impressions,
            sum(keywords_report.attributedConversions1dSameSKU) attributedConversions1dSameSKU,
            sum(keywords_report.attributedSales1d) attributedSales1d,
            sum(keywords_report.attributedConversions1d) attributedConversions1d,
            sum(keywords_report.attributedSales1dSameSKU) attributedSales1dSameSKU,
            sum(keywords_report.cost)/sum(keywords_report.clicks) as cpc,
            sum(keywords_report.cost)/sum(keywords_report.attributedSales1d)*100 as acos'));

        $query->LeftJoin('reqest_report_api', function ($join) use ($beginData, &$endDate){
            $join->where('reqest_report_api.type', '=', 'keywords')
                ->where('reqest_report_api.amazn_report_date', '>=', $beginData)
                ->where('reqest_report_api.amazn_report_date', '<=', $endDate);
        });
        $query->LeftJoin('keywords_report', function ($join) use ($beginData, &$endDate){
            $join->on( 'keywords.id', '=', 'keywords_report.keywordId')
                ->on('keywords_report.request_report_id', '=', 'reqest_report_api.id');
        });

        $query = $query->groupBy('keywords.id');
        $query = $query->where('keywords.campaign_id', $campaignId);
        $query = $query->where('keywords.ad_group_id', $adgroupId);

        if(!empty($criteria['globalFilter']))
            $query = $query->where('keywords.keyword_text', 'LIKE', '%' . $criteria['globalFilter'] . '%');

        if($criteria['sortField'] && $criteria['sortOrder'])
            $query = $query->orderBy($criteria['sortField'], $criteria['sortOrder']);

        if(!is_null($skip) || !is_null($rows)) return $query->offset($skip)->limit($rows)->get();
        //$results = $query->toSql();
        //var_dump($results);
        return $query->get();
    }

    public static function getKeywordsCount($campaignId, $adgroupId, $criteria) {
        $query = DB::table('keywords');
        $query = $query->where('keywords.campaign_id', $campaignId);
        $query = $query->where('keywords.ad_group_id', $adgroupId);
        if(!empty($criteria['globalFilter']))
            $query = $query->where('keywords.keyword_text', 'LIKE', '%' . $criteria['globalFilter'] . '%');

        //$query = $query->groupBy('c.id');
        return $query->count();
    }
}
