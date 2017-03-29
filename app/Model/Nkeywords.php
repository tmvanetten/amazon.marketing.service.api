<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Nkeywords extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'campaign_id', 'ad_group_id', 'keyword_text', 'match_type', 'state', 'created_at', 'updated_at',
    ];

    protected $table = 'nkeywords';
}
