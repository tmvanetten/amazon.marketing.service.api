<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;

class KeywordsReport extends Model
{
    protected $table = 'keywords_report';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'request_report_id',
        'keywordId',
        'campaignId',
        'adGroupId',
        'keywordText',
        'matchType',
        'state',
        'bid',
        'clicks',
        'cost',
        'impressions',
        'attributedConversions1dSameSKU',
        'attributedSales1d',
        'attributedConversions1d',
        'attributedSales1dSameSKU'
    ];


    protected $hidden = [
        'update_at', 'created_at',
    ];
}
