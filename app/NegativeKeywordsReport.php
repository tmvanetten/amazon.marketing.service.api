<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;

class NegativeKeywordsReport extends Model
{
    protected $table = 'negative_keywords';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'keywordId',
        'ad_group_id',
        'campaignId',
        'adGroupId',
        'enabled',
        'keywordText',
        'matchType',
        'state'
    ];


    protected $hidden = [
        'update_at', 'created_at',
    ];
}
