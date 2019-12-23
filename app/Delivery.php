<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Business;
use Modules\Superadmin\Entities\Subscription;

class Delivery extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];
    public static function canDelivery($business_id)
    {
        if (!auth()->user()->can('access_delivery')) {
            return false;
        }
        $enabled_module = Business::find($business_id)->enabled_modules;
        if( is_null($enabled_module) || !in_array('delivery', $enabled_module) )
            return false;
        
        $permitted_locations = auth()->user()->permitted_locations();
        $can_delivery = false;
        if ($permitted_locations == 'all') {
            return true;
        }
        else{
            foreach($permitted_locations as $loc_id)
                $can_delivery = ($can_delivery or Delivery::where('business_loc_id', $loc_id)->first()->enabled);
        }
        
        if( !$can_delivery )
            return false;
        return true;
    }

    //return true when a location can delivery.
    public static function getLocationDelivery($business_id, $location_id)
    {
        $delivery = Delivery::where('business_loc_id', $location_id)->first();
        if( empty($delivery) || $delivery->enabled == 0 )
            return null;
        
        //get active subscription on business
        $date_today = \Carbon::today();
        $active_subscription = Subscription::active_subscription($business_id);
        if(!$active_subscription->is_delivery)
            return null;
        return $delivery;
    }
}
