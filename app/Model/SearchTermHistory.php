<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class SearchTermHistory extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'search_term', 'message','type', 'campaign_id', 'ad_group_id', 'match_type', 'created_at', 'updated_at',
    ];

    protected $table = 'search_term_history';
}
