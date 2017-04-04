<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use DB;

class Campaigns extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'name', 'campaign_type', 'targeting_type', 'daily_budget', 'state', 'run_strategy', 'created_at', 'updated_at',
    ];

    protected $table = 'campaigns';

    /**
     * Get the adgroups id and name.
     */
    public function adgroupsSimple ()
    {
        return $this->hasMany('App\Model\Adgroups', 'campaign_id')
            ->orderBy('name')
            ->select('id', 'name');
    }

    /**
     * Get the adgroups detailed.
     */
    public function adgroupsAll ()
    {
        return $this->hasMany('App\Model\Adgroups', 'campaign_id')->orderBy('name');
    }

    /**
     * Get campaigns by date.
     *
     * @var $selectedDate string
     * @var $skip integer
     * @var $rows integer
     * @return array
     */
    public static function getCampaigns($criteria, $beginData, $endDate, $skip = null, $rows = null){
        $query = DB::table('campaigns as c')->select(DB::raw('
            c.id as id,
            c.id as campaignId,
            c.run_strategy as enabled,
            c.name as name,
            c.campaign_type as campaignType,
            c.targeting_type as targetingType,
            c.daily_budget as dailyBudget,
            c.state as state,
            sum(campaign_report.clicks) clicks,
            sum(campaign_report.cost) cost,
            sum(campaign_report.impressions) impressions,
            sum(campaign_report.attributedSales1d) sales,
            sum(campaign_report.attributedSales1d) attributedSales1d,
            sum(campaign_report.attributedConversions1d) attributedConversions1d,
            sum(campaign_report.cost)/sum(campaign_report.clicks) as cpc,
            sum(campaign_report.cost)/sum(campaign_report.attributedSales1d)*100 as acos,
            (SELECT COUNT(a.id) FROM adgroups a WHERE a.campaign_id=c.id) as adGroupsCount'));

        $query->LeftJoin('reqest_report_api', function ($join) use ($beginData, &$endDate){
            $join->where('reqest_report_api.type', '=', 'campaigns')
                ->where('reqest_report_api.amazn_report_date', '>=', $beginData)
                ->where('reqest_report_api.amazn_report_date', '<=', $endDate);
        });
        $query->LeftJoin('campaign_report', function ($join) use ($beginData, &$endDate){
            $join->on( 'c.id', '=', 'campaign_report.campaignId')
                ->on('campaign_report.request_report_id', '=', 'reqest_report_api.id');
        });
        if(!empty($criteria['globalFilter']))
            $query = $query->where('c.name', 'LIKE', '%' . $criteria['globalFilter'] . '%');
        if($criteria['filters'] && is_array($criteria['filters']) && count($criteria['filters'])) {
            foreach($criteria['filters'] as $field => $filter) {
                switch($filter['matchMode']) {
                    case 'like':
                        $query = $query->having($field, 'LIKE', '%' . $filter['value'] . '%');
                        break;
                    case 'equals':
                        $query = $query->having($field, '=', $filter['value']);
                }
            }
        }

        $query = $query->groupBy('c.id');

        if($criteria['sortField'] && $criteria['sortOrder'])
            $query = $query->orderBy($criteria['sortField'], $criteria['sortOrder']);

        if(!is_null($skip) || !is_null($rows)) return $query->offset($skip)->limit($rows)->get();
        //$results = $query->toSql();
        //var_dump($results);
        return $query->get();
    }


    public static function getBiddableAutoCampaign($recentBeginDate, $pastEndDate, $endDate) {
        $query = DB::table('adgroups')->select(DB::raw('
            adgroups.id as adGroupId,
            campaigns.id as campaignId,
            campaigns.name as campaignName,
            adgroups.adgroupName,
            adgroups.default_bid as defaultBid,
            sum(ad_group_report_recent.clicks) clicks_recent,
            sum(ad_group_report_recent.cost) cost_recent,
            sum(ad_group_report_recent.impressions) impressions_recent,
            sum(ad_group_report_recent.attributedSales1d) sales_recent,
            sum(ad_group_report_recent.attributedSales1d) attributedSales1d_recent,
            sum(ad_group_report_recent.attributedConversions1d) attributedConversions1d_recent,
            sum(ad_group_report_recent.cost)/sum(ad_group_report_recent.clicks) as cpc_recent,
            sum(ad_group_report_recent.cost)/sum(ad_group_report_recent.attributedSales1d)*100 as acos_recent,
            sum(ad_group_report_past.clicks) clicks_past,
            sum(ad_group_report_past.cost) cost_past,
            sum(ad_group_report_past.impressions) impressions_past,
            sum(ad_group_report_past.attributedSales1d) sales_past,
            sum(ad_group_report_past.attributedSales1d) attributedSales1d_past,
            sum(ad_group_report_past.attributedConversions1d) attributedConversions1d_past,
            sum(ad_group_report_past.cost)/sum(ad_group_report_past.clicks) as cpc_past,
            sum(ad_group_report_past.cost)/sum(ad_group_report_past.attributedSales1d)*100 as acos_past,
            max(strategy_history.created_at) as strategyDate'));
        $query->RightJoin('campaigns', function ($join){
            $join->on( 'campaigns.id', '=', 'adgroups.campaign_id');
        });
        $query->LeftJoin('strategy_history', function ($join){
            $join->on( 'strategy_history.ad_group_id', '=', 'adgroups.id');
        });
        $query->LeftJoin('reqest_report_api as reqest_report_api_recent', function ($join) use ($recentBeginDate, $endDate){
            $join->where('reqest_report_api_recent.type', '=', 'adGroups')
                ->where('reqest_report_api_recent.amazn_report_date', '>=', $recentBeginDate)
                ->where('reqest_report_api_recent.amazn_report_date', '<=', $endDate);
        });
        $query->LeftJoin('ad_group_report as ad_group_report_recent', function ($join) {
            $join->on( 'adgroups.id', '=', 'ad_group_report_recent.adGroupId')
                ->on('ad_group_report_recent.request_report_id', '=', 'reqest_report_api_recent.id');
        });
        $query->LeftJoin('reqest_report_api as reqest_report_api_past', function ($join) use ($pastEndDate, $endDate){
            $join->where('reqest_report_api_past.type', '=', 'adGroups')
                ->where('reqest_report_api_past.amazn_report_date', '>=', $pastEndDate)
                ->where('reqest_report_api_past.amazn_report_date', '<=', $endDate);
        });
        $query->LeftJoin('ad_group_report as ad_group_report_past', function ($join) {
            $join->on( 'adgroups.id', '=', 'ad_group_report_past.adGroupId')
                ->on('ad_group_report_past.request_report_id', '=', 'reqest_report_api_past.id');
        });

        $query = $query->where('campaigns.run_strategy', '=', true);
        $query = $query->groupBy('adgroups.id');
        $data = [];
        $collection = $query->get();
        foreach($collection as $item) {
            $data[$item->adGroupId] = $item;
        }
        return $data;
    }
}
