<?php

namespace App;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class KeywordsReport extends Model
{
    protected $table = 'keywords_report';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'request_report_id',
        'keywordId',
        'campaignId',
        'adGroupId',
        'keywordText',
        'matchType',
        'state',
        'bid',
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
    public static function getKeywords($reports_ids, $campaignId, $adGroupId, $criteria,  $skip = null, $rows = null){
        $query = DB::table('keywords_report')->select(DB::raw('id, request_report_id, keywordId, adGroupId, campaignId, enabled, keywordText, matchType,
         state, bid, sum(clicks) clicks, sum(cost) cost, avg(impressions) impressions, sum(attributedConversions1dSameSKU) attributedConversions1dSameSKU,
            sum(attributedSales1d) attributedSales1d, sum(attributedConversions1d) attributedConversions1d, sum(attributedSales1dSameSKU) attributedSales1dSameSKU,
            sum(cost)/sum(clicks) as cpc, sum(cost)/sum(attributedSales1d)*100 as acos'));

        if(!empty($criteria['globalFilter'])) $query = $query->where('keywordText', 'LIKE', '%' . $criteria['globalFilter'] . '%');

        $query = $query->where('campaignId', $campaignId)->where('adGroupId', $adGroupId)->whereIn('request_report_id', $reports_ids)->groupBy('keywordId');

        if($criteria['sortField'] && $criteria['sortOrder'])
            $query = $query->orderBy($criteria['sortField'], $criteria['sortOrder']);

        if(!is_null($skip) || !is_null($rows)) return $query->offset($skip)->limit($rows)->get();
        return $query->get();
    }
}
