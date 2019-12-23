<?php

namespace Modules\Superadmin\Http\Controllers;

use Illuminate\Routing\Controller;

use Modules\Superadmin\Entities\Subscription;

class BaseController extends Controller
{

    /**
     * Returns the list of all configured payment gateway
     * @return Response
     */
    protected function _payment_gateways(){
        $gateways = [];
        
        //Check if configured or not
        if(env('STRIPE_PUB_KEY') && env('STRIPE_SECRET_KEY')){
            $gateways['stripe'] = 'Stripe';
        }

        if((env('PAYPAL_SANDBOX_API_USERNAME') && env('PAYPAL_SANDBOX_API_PASSWORD')  && env('PAYPAL_SANDBOX_API_SECRET')) || (env('PAYPAL_LIVE_API_USERNAME') && env('PAYPAL_LIVE_API_PASSWORD')  && env('PAYPAL_LIVE_API_SECRET')) ){
            $gateways['paypal'] = 'PayPal';
        }

        $gateways['offline'] = 'Offline';

        return $gateways;
    }

    /**
     * Enter details for subscriptions
     * @return object
     */
    protected function _add_subscription($business_id, $package, $gateway, $payment_transaction_id, $user_id, $is_superadmin = false){

        $subscription = ['business_id' => $business_id,
                        'package_id' => $package->id,
                        'paid_via' => $gateway,
                        'payment_transaction_id' => $payment_transaction_id
                    ];

        if($gateway == 'offline' && !$is_superadmin){
            //If offline then dates will be decided when approved by superadmin
            $subscription['start_date'] = null;
            $subscription['end_date'] = null;
            $subscription['trial_end_date'] = null;
            $subscription['status'] = 'waiting';
        } else {
            $subscription_end_date = Subscription::end_date($business_id);
            $subscription['start_date'] = $subscription_end_date->toDateString();

            if($package->interval == 'days'){
                $subscription['end_date'] = $subscription_end_date->addDays($package->interval_count)->toDateString();
            } elseif($package->interval == 'months'){
                $subscription['end_date'] = $subscription_end_date->addMonths($package->interval_count)->toDateString();
            } elseif($package->interval == 'years'){
                $subscription['end_date'] = $subscription_end_date->addYears($package->interval_count)->toDateString();
            }
            
            $subscription['trial_end_date'] = $subscription_end_date->addDays($package->trial_days);

            $subscription['status'] = 'approved';
        }

        $subscription['package_price'] = $package->price;
        $subscription['package_details'] = ['location_count' => $package->location_count, 
                'user_count' => $package->user_count, 
                'product_count' => $package->product_count, 
                'invoice_count' => $package->invoice_count,
                'name' => $package->name
            ];
        $subscription['created_id'] = $user_id;

        $subscription = Subscription::create($subscription);

        return $subscription;
    }

}
