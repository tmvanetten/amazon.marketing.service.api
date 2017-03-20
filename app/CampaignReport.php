<?php

namespace App;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class CampaignReport extends Model
{
    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'mysql';

    protected $table = 'campaign_report';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'request_report_id',
        'campaignId',
        'enabled',
        'name',
        'campaignType',
        'targetingType',
        'premiumBidAdjustment',
        'dailyBudget',
        'startDate',
        'endDate',
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
     * Get campaigns by date.
     *
     * @var $selectedDate string
     * @var $skip integer
     * @var $rows integer
     * @return array
     */
    public static function getCampaigns($reports_ids, $skip = null, $rows = null){
        $query = DB::table('campaign_report')->select(DB::raw('id, request_report_id, campaignId, enabled, name, campaignType, targetingType, premiumBidAdjustment,
            dailyBudget, startDate, state, avg(clicks) clicks, avg(cost) cost, avg(impressions) impressions, avg(attributedConversions1dSameSKU) attributedConversions1dSameSKU,
            avg(attributedSales1d) attributedSales1d, avg(attributedConversions1d) attributedConversions1d, avg(attributedSales1dSameSKU) attributedSales1dSameSKU'))
            ->whereIn('request_report_id', $reports_ids)->groupBy('campaignId');

        if(!is_null($skip) || !is_null($rows)) return $query->offset($skip)->limit($rows)->get();
        return $query->get();
    }
}