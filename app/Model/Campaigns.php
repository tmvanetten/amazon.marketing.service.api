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
                        break;
                    case 'in':
                        $query = $query->havingRaw($field . ' >= ' . $filter['value'][0] . ' AND ' . $field . ' <= ' . $filter['value'][1]);
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


    public static function getBiddableAutoCampaign($beginDate, $endDate, $runDate) {
        $query = DB::table('adgroups')->select(DB::raw('
            adgroups.id as adGroupId,
            campaigns.id as campaignId,
            adgroups.default_bid as defaultBid,
            sum(ad_group_report.clicks) clicks,
            sum(ad_group_report.cost) cost,
            sum(ad_group_report.impressions) impressions,
            sum(ad_group_report.attributedSales1d) sales,
            sum(ad_group_report.attributedConversions1d) conversions,
            sum(ad_group_report.cost)/sum(ad_group_report.clicks) as cpc,
            sum(ad_group_report.cost)/sum(ad_group_report.attributedSales1d)*100 as acos,
            (SELECT max(strategy_history.created_at) FROM strategy_history WHERE strategy_history.ad_group_id = adgroups.id) as strategyDate'));
        $query->RightJoin('campaigns', function ($join){
            $join->on( 'campaigns.id', '=', 'adgroups.campaign_id');
        });
        $query->LeftJoin('ad_group_report', function ($join) {
            $join->on( 'adgroups.id', '=', 'ad_group_report.adGroupId');
        });
        $query->LeftJoin('reqest_report_api', function ($join) {
            $join->on('ad_group_report.request_report_id', '=', 'reqest_report_api.id');
        });
        $query = $query->where('campaigns.run_strategy', '=', true);
        $query = $query->where('campaigns.targeting_type', '=', 'auto');
        $query = $query->where('reqest_report_api.type', '=', 'adGroups');
        $query = $query->where('reqest_report_api.amazn_report_date', '>=', $beginDate);
        $query = $query->where('reqest_report_api.amazn_report_date', '<=', $endDate);
        $query = $query->groupBy('adgroups.id');
        $query = $query->havingRaw("strategyDate IS NULL OR strategyDate < '$runDate'");
        $data = [];
        $collection = $query->get();
        foreach($collection as $item) {
            $data[$item->adGroupId] = $item;
        }
        return $data;
    }

    public static function getBiddableManualCampaign($beginDate, $endDate, $runDate) {
        $query = DB::table('keywords')->select(DB::raw('
            keywords.id as keywordId,
            adgroups.id as adGroupId,
            campaigns.id as campaignId,
            keywords.bid as defaultBid,
            sum(keywords_report.clicks) clicks,
            sum(keywords_report.cost) cost,
            sum(keywords_report.impressions) impressions,
            sum(keywords_report.attributedSales1d) sales,
            sum(keywords_report.attributedConversions1d) conversions,
            sum(keywords_report.cost)/sum(keywords_report.clicks) as cpc,
            sum(keywords_report.cost)/sum(keywords_report.attributedSales1d)*100 as acos,
            (SELECT max(strategy_history.created_at) FROM strategy_history WHERE strategy_history.keyword_id = keywords.id) as strategyDate'));
        $query->RightJoin('campaigns', function ($join){
            $join->on( 'campaigns.id', '=', 'keywords.campaign_id');
        });
        $query->RightJoin('adgroups', function ($join){
            $join->on( 'adgroups.id', '=', 'keywords.ad_group_id');
        });
        $query->LeftJoin('keywords_report', function ($join) {
            $join->on( 'keywords.id', '=', 'keywords_report.keywordId');
        });
        $query->LeftJoin('reqest_report_api', function ($join) {
            $join->on('keywords_report.request_report_id', '=', 'reqest_report_api.id');
        });
        $query = $query->where('keywords.bid', '>', 0);
        $query = $query->where('campaigns.run_strategy', '=', true);
        $query = $query->where('campaigns.targeting_type', '=', 'manual');
        $query = $query->where('reqest_report_api.type', '=', 'keywords');
        $query = $query->where('reqest_report_api.amazn_report_date', '>=', $beginDate);
        $query = $query->where('reqest_report_api.amazn_report_date', '<=', $endDate);
        $query = $query->groupBy('keywords.id');
        $query = $query->havingRaw("strategyDate IS NULL OR strategyDate < '$runDate'");
        $data = [];
        $collection = $query->get();
        foreach($collection as $item) {
            $data[$item->keywordId] = $item;
        }
        return $data;
    }
}
