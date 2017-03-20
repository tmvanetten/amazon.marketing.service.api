<?php

namespace App;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class AdGroupsReport extends Model
{
    protected $table = 'ad_group_report';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'adGroupId',
        'enabled',
        'name',
        'campaignId',
        'defaultBid',
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
    public static function getAdgroups($reports_ids, $campaignId, $skip = null, $rows = null){
        $query = DB::table('ad_group_report')->select(DB::raw('id, request_report_id, adGroupId, campaignId, enabled, name, defaultBid,
         state, avg(clicks) clicks, avg(cost) cost, avg(impressions) impressions, avg(attributedConversions1dSameSKU) attributedConversions1dSameSKU,
            avg(attributedSales1d) attributedSales1d, avg(attributedConversions1d) attributedConversions1d, avg(attributedSales1dSameSKU) attributedSales1dSameSKU'))
            ->where('campaignId', $campaignId)->whereIn('request_report_id', $reports_ids)->groupBy('adGroupId');

        if(!is_null($skip) || !is_null($rows)) return $query->offset($skip)->limit($rows)->get();
        return $query->get();
    }
}
