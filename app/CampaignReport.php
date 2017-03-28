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
    public static function getCampaigns($reports_ids, $criteria, $adGropuReportId, $skip = null, $rows = null){
        $query = DB::table('campaign_report as c')->select(DB::raw('c.id as id, c.request_report_id as request_report_id,
            c.campaignId as campaignId, c.enabled as enabled, c.name as name, c.campaignType as campaignType,
            c.targetingType as targetingType, c.premiumBidAdjustment as premiumBidAdjustment,
            c.dailyBudget as dailyBudget, c.startDate as startDate, c.state as state, sum(c.clicks) clicks, sum(c.cost) cost, sum(c.impressions) impressions, sum(c.attributedSales1d) sales,
            sum(c.attributedSales1d) attributedSales1d, sum(c.attributedConversions1d) attributedConversions1d, sum(c.cost)/sum(c.clicks) as cpc, sum(c.cost)/sum(c.attributedSales1d)*100 as acos,
            (SELECT COUNT(a.id) FROM ad_group_report a WHERE a.campaignId=c.campaignId AND a.request_report_id='. $adGropuReportId .') as adGroupsCount'));
        if(!empty($criteria['globalFilter'])) $query = $query->where('name', 'LIKE', '%' . $criteria['globalFilter'] . '%');
        $query = $query->whereIn('request_report_id', $reports_ids)->groupBy('campaignId');
        if($criteria['sortField'] && $criteria['sortOrder'])
            $query = $query->orderBy($criteria['sortField'], $criteria['sortOrder']);

        if(!is_null($skip) || !is_null($rows)) return $query->offset($skip)->limit($rows)->get();
        //$results = $query->toSql();
        //var_dump($results);
        return $query->get();
    }
}
