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
        'name',
        'campaignType',
        'targetingType',
        'premiumBidAdjustment',
        'dailyBudget',
        'startDate',
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
     * @return array
     */
    public static function getCampaigns($selectedDate){
        return DB::table('campaign_report')
          ->join('reqest_report_api', function ($join) use ($selectedDate){
              $join->on('reqest_report_api.id', '=', 'campaign_report.request_report_id')
                  ->where('reqest_report_api.amazn_report_date', '=', $selectedDate);
          })->get();
    }
}
