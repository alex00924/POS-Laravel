<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RedeemPoints extends Model
{
    protected $table = 'redeem_points';
    protected $fillable = ['business_id', 'business_loc_id', 'points', 'redeem_result'];

    function business() {
        return $this->belongsTo(\App\Business::class, 'business_id');
    }

    function location() {
        return $this->belongsTo(\App\BusinessLocation::class, 'business_loc_id');
    }
}
