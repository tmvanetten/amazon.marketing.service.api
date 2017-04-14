<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use DB;

class StrategyHistory extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'campaign_id', 'ad_group_id', 'keyword_id', 'updated_by', 'created_at', 'updated_at', 'new_bid', 'old_bid'
     ];

    protected $table = 'strategy_history';

    public static function getHistories($criteria, $skip=null, $rows=null) {
        $query = SELF::query();
        $query = $query->select(DB::raw('
            strategy_history.*,
            campaigns.name as campaginName,
            adgroups.name as adgroupName,
            keywords.keyword_text as keywordName
        '));
        $query->LeftJoin('campaigns', function ($join){
            $join->on( 'campaigns.id', '=', 'strategy_history.campaign_id');
        });
        $query->LeftJoin('adgroups', function ($join){
            $join->on( 'adgroups.id', '=', 'strategy_history.ad_group_id');
        });
        $query->LeftJoin('keywords', function ($join){
            $join->on( 'keywords.id', '=', 'strategy_history.keyword_id');
        });
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
}
