<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;

class ReportItems extends Model
{
    protected $table = 'report_items';
    protected $hidden = [
        'update_at', 'created_at',
    ];
}
