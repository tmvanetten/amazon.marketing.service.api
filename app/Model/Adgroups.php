<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

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
}
