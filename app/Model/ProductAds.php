<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ProductAds extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'campaign_id', 'ad_group_id', 'sku', 'state', 'created_at', 'updated_at', 'asin'
    ];

    protected $table = 'productads';
}
