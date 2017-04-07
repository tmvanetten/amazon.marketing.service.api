<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use DB;

class Adgroups extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'campaign_id', 'name', 'default_bid', 'state', 'created_at', 'updated_at',
    ];

    protected $table = 'adgroups';

    /**
     * Get campaigns by date.
     *
     * @var $selectedDate string
     * @var $skip integer
     * @var $rows integer
     * @return array
     */
    public static function getAdgroups($campaignId, $criteria, $beginData, $endDate, $skip = null, $rows = null){
        $query = DB::table('adgroups')->select(DB::raw('
            adgroups.id,
            adgroups.id as adGroupId,
            adgroups.campaign_id as campaignId,
            adgroups.name,
            adgroups.default_bid as defaultBid,
            adgroups.state,
            sum(ad_group_report.clicks) clicks,
            sum(ad_group_report.cost) cost,
            sum(ad_group_report.impressions) impressions,
            sum(ad_group_report.attributedSales1d) sales,
            sum(ad_group_report.attributedSales1d) attributedSales1d,
            sum(ad_group_report.attributedConversions1d) attributedConversions1d,
            sum(ad_group_report.cost)/sum(ad_group_report.clicks) as cpc,
            sum(ad_group_report.cost)/sum(ad_group_report.attributedSales1d)*100 as acos,
            history.created_at as strategyDate,
            history.new_bid,
            history.old_bid,
            history.updated_by'));

        $query->LeftJoin('reqest_report_api', function ($join) use ($beginData, &$endDate){
            $join->where('reqest_report_api.type', '=', 'adGroups')
                ->where('reqest_report_api.amazn_report_date', '>=', $beginData)
                ->where('reqest_report_api.amazn_report_date', '<=', $endDate);
        });
        $query->LeftJoin('ad_group_report', function ($join) use ($beginData, &$endDate){
            $join->on( 'adgroups.id', '=', 'ad_group_report.adGroupId')
                ->on('ad_group_report.request_report_id', '=', 'reqest_report_api.id');
        });

        $query->leftJoin(
            DB::raw("(
            SELECT
                history_sort.ad_group_id,
                history_sort.created_at,
                history_sort.new_bid,
                history_sort.old_bid,
                history_sort.updated_by
            FROM (
                SELECT
                    ad_group_id,
                    created_at,
                    new_bid,
                    old_bid,
                    updated_by
              FROM strategy_history h1
              WHERE h1.created_at = (
                SELECT MAX(h2.created_at)
                from strategy_history h2
                LEFT JOIN campaigns ON h2.campaign_id = campaigns.id
                where h1.ad_group_id = h2.ad_group_id
                AND campaigns.targeting_type = 'auto'
              )
            ) history_sort) `history`
        "), 'history.ad_group_id', '=', 'adgroups.id'
        );

        $query = $query->groupBy('adgroups.id');
        $query = $query->where('adgroups.campaign_id', $campaignId);

        if(!empty($criteria['globalFilter']))
            $query = $query->where('adgroups.name', 'LIKE', '%' . $criteria['globalFilter'] . '%');
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
        if($criteria['sortField'] && $criteria['sortOrder'])
            $query = $query->orderBy($criteria['sortField'], $criteria['sortOrder']);

        if(!is_null($skip) || !is_null($rows)) return $query->offset($skip)->limit($rows)->get();
        //$results = $query->toSql();
        //var_dump($results);
        return $query->get();
    }

    public static function getAdGroupsHistory($adgroupId, $beginDate, $endDate) {
        $query = SELF::query();
        $query = $query->select(DB::raw('
            ad_group_report.defaultBid as bid,
            ad_group_report.clicks,
            ad_group_report.cost,
            ad_group_report.attributedSales1d as sales,
            reqest_report_api.amazn_report_date'));
        $query->LeftJoin('reqest_report_api', function ($join) use ($beginDate, &$endDate){
            $join->where('reqest_report_api.type', '=', 'adGroups')
                ->where('reqest_report_api.amazn_report_date', '>=', $beginDate)
                ->where('reqest_report_api.amazn_report_date', '<=', $endDate);
        });
        $query->LeftJoin('ad_group_report', function ($join){
            $join->on( 'adgroups.id', '=', 'ad_group_report.adGroupId')
                ->on('ad_group_report.request_report_id', '=', 'reqest_report_api.id');
        });
        $query = $query->where('adgroups.id', '=', $adgroupId);
        $query = $query->orderBy('amazn_report_date', 'asc');
        return $query->get();
    }

    public static function getAdgroupCount($campaignId, $criteria) {
        $query = DB::table('adgroups')->select(DB::raw('
            count(*) as count'));
        $query = $query->where('adgroups.campaign_id', $campaignId);
        if(!empty($criteria['globalFilter']))
            $query = $query->where('adgroups.name', 'LIKE', '%' . $criteria['globalFilter'] . '%');

        //$query = $query->groupBy('c.id');
        return $query->count();
    }
}
