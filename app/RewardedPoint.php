<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RewardedPoint extends Model
{
    protected $table = 'rewarded_points';
    protected $fillable = ['business_id', 'location_id', 'points', 'point_ratio', 'total_price', 'cart_uid', 'purchased'];
}
