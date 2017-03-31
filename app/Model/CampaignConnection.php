<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CampaignConnection extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'manual_id', 'auto_id', 'created_at', 'updated_at',
    ];

    protected $table = 'campaign_connection';

    /**
     * Get campaigns by date.
     *
     * @var $skip integer
     * @var $rows integer
     * @return array
     */
    public static function getConnectedCampaigns($criteria, $skip = null, $rows = null){
        $query = DB::table('campaign_connection as cc')->select(DB::raw('cc.id as id, cc.manual_id as manual_id,
            cc.auto_id as auto_id, a.name as manual_name,
            b.name as auto_name'));
        $query = $query->join('campaigns as a', 'a.id', '=', 'cc.manual_id');
        $query = $query->join('campaigns as b', 'b.id', '=', 'cc.auto_id');
        if(!empty($criteria['filters'])){
            foreach($criteria['filters'] as $key => $value){
                if($value) $query = $query->where($key, 'LIKE', '%' . $value . '%');
            }
        }
        if($criteria['sortField'] && $criteria['sortOrder'])
            $query = $query->orderBy($criteria['sortField'], $criteria['sortOrder']);
        if(!is_null($skip) || !is_null($rows)) return $query->offset($skip)->limit($rows)->get();
        return $query->get();
    }

}
