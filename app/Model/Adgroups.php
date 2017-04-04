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
            sum(ad_group_report.cost)/sum(ad_group_report.attributedSales1d)*100 as acos'));

        $query->LeftJoin('reqest_report_api', function ($join) use ($beginData, &$endDate){
            $join->where('reqest_report_api.type', '=', 'adGroups')
                ->where('reqest_report_api.amazn_report_date', '>=', $beginData)
                ->where('reqest_report_api.amazn_report_date', '<=', $endDate);
        });
        $query->LeftJoin('ad_group_report', function ($join) use ($beginData, &$endDate){
            $join->on( 'adgroups.id', '=', 'ad_group_report.adGroupId')
                ->on('ad_group_report.request_report_id', '=', 'reqest_report_api.id');
        });

        $query = $query->groupBy('adgroups.id');
        $query = $query->where('adgroups.campaign_id', $campaignId);

        if(!empty($criteria['globalFilter']))
            $query = $query->where('adgroups.name', 'LIKE', '%' . $criteria['globalFilter'] . '%');

        if($criteria['sortField'] && $criteria['sortOrder'])
            $query = $query->orderBy($criteria['sortField'], $criteria['sortOrder']);

        if(!is_null($skip) || !is_null($rows)) return $query->offset($skip)->limit($rows)->get();
        //$results = $query->toSql();
        //var_dump($results);
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
