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
    public static function getKeywords($reports_ids, $campaignId, $adGroupId,  $skip = null, $rows = null){
        $query = DB::table('keywords_report')->select(DB::raw('id, request_report_id, keywordId, adGroupId, campaignId, enabled, keywordText, matchType,
         state, bid, avg(clicks) clicks, avg(cost) cost, avg(impressions) impressions, avg(attributedConversions1dSameSKU) attributedConversions1dSameSKU,
            avg(attributedSales1d) attributedSales1d, avg(attributedConversions1d) attributedConversions1d, avg(attributedSales1dSameSKU) attributedSales1dSameSKU'))
            ->where('campaignId', $campaignId)->where('adGroupId', $adGroupId)->whereIn('request_report_id', $reports_ids)->groupBy('keywordId');

        if(!is_null($skip) || !is_null($rows)) return $query->offset($skip)->limit($rows)->get();
        return $query->get();
    }
}
