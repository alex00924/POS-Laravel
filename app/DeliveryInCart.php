<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Business;

class DeliveryInCart extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];
}
