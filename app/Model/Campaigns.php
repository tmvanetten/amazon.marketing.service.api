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

        if($beginData && $endDate)
            //$query = $query->whereBetween('reqest_report_api.amazn_report_date', [$beginData,$endDate]);

        $query = $query->groupBy('c.id');

        if($criteria['sortField'] && $criteria['sortOrder'])
            $query = $query->orderBy($criteria['sortField'], $criteria['sortOrder']);

        if(!is_null($skip) || !is_null($rows)) return $query->offset($skip)->limit($rows)->get();
        //$results = $query->toSql();
        //var_dump($results);
        return $query->get();
    }

    public static function getCampaignsCount($criteria) {
        $query = DB::table('campaigns as c')->select(DB::raw('
            count(*) as count'));

        if(!empty($criteria['globalFilter']))
            $query = $query->where('c.name', 'LIKE', '%' . $criteria['globalFilter'] . '%');

        //$query = $query->groupBy('c.id');
        return $query->count();
    }

}
