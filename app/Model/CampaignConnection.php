<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

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
}
