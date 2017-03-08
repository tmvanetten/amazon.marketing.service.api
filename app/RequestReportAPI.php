<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;

class RequestReportAPI extends Model
{
    protected $table = 'reqest_report_api';
    protected $hidden = [
        'update_at', 'created_at',
    ];
}
