<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

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

}
