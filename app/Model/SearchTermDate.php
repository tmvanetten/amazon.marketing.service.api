<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class SearchTermDate extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'beginDate', 'endDate', 'created_at', 'updated_at',
    ];

    protected $table = 'search_term_date';
}
