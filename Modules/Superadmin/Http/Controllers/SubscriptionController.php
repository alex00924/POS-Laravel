<?php

namespace Modules\Superadmin\Http\Controllers;

use Modules\Superadmin\Entities\Subscription,
    Modules\Superadmin\Entities\Package,
    App\System,
    App\Business;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Superadmin\Notifications\SubscriptionOfflinePaymentActivationConfirmation;

use \Notification,
    Stripe\Stripe,
    Stripe\Customer,
    Stripe\Charge;

use Srmklive\PayPal\Services\ExpressCheckout;
use Yajra\DataTables\Facades\DataTables;

class SubscriptionController extends BaseController
{
    protected $provider;

    public function __construct(){

        if ( ! defined('CURL_SSLVERSION_TLSv1_2')) {
            define('CURL_SSLVERSION_TLSv1_2', 6);
        }

        if ( ! defined('CURLOPT_SSLVERSION')) {
            define('CURLOPT_SSLVERSION', 6);
        }
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        if (!auth()->user()->can('subscribe')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        //Get active subscription and upcoming subscriptions.
        $active = Subscription::active_subscription($business_id);
        
        $nexts = Subscription::upcoming_subscriptions($business_id);
        $waiting = Subscription::waiting_approval($business_id);

        $packages = Package::active()->orderby('sort_order')->get();

        return view('superadmin::subscription.index')
            ->with(compact('packages', 'active', 'nexts', 'waiting'));
    }

    /**
     * Show pay form for a new package.
     * @return Response
     */
    public function pay($package_id, $form_register = null)
    {
        if (!auth()->user()->can('subscribe')) {
            abort(403, 'Unauthorized action.');
        }

        try{
            DB::beginTransaction();

            $business_id = request()->session()->get('user.business_id');

            $package = Package::active()->find($package_id);

            //Check for free package & subscribe it.
            if($package->price == 0){
                $gateway = NULL;
                $payment_transaction_id = 'FREE';
                $user_id = request()->session()->get('user.id');

                $this->_add_subscription($business_id, $package, $gateway, $payment_transaction_id, $user_id);

                DB::commit();

                if(empty($form_register)){
                    $output = ['success' => 1, 'msg' => __('lang_v1.success')];
                    return redirect()
                        ->action('\Modules\Superadmin\Http\Controllers\SubscriptionController@index')
                        ->with('status', $output);
                } else {
                    $output = ['success' => 1, 'msg' => __('superadmin::lang.registered_and_subscribed')];
                    return redirect()
                        ->action('\Modules\Superadmin\Http\Controllers\SubscriptionController@index')
                        ->with('status', $output);
                }
            }

            $gateways = $this->_payment_gateways();

            $system_currency = System::getCurrency();
            
            DB::commit();

            if(empty($form_register)){
                $layout = 'layouts.app';
            } else {
                $layout = 'layouts.auth';
            }

            return view('superadmin::subscription.pay')
                ->with(compact('package', 'gateways', 'system_currency', 'layout'));

        } catch(\Exception $e){

            DB::rollBack();

            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
            $output = ['success' => 0, 'msg' => "File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage()];

            return redirect()
                ->action('\Modules\Superadmin\Http\Controllers\SubscriptionController@index')
                ->with('status', $output);
        }
    }

    /**
     * Show pay form for a new package.
     * @return Response
     */
    public function registerPay($package_id)
    {
        return $this->pay($package_id, 1);
    }

    /**
     * Save the payment details and add subscription details
     * @return Response
     */
    public function confirm($package_id, Request $request){
        if (!auth()->user()->can('subscribe')) {
            abort(403, 'Unauthorized action.');
        }

        try{

            //Disable in demo
            if (config('app.env') == 'demo') {
                $output = ['success' => 0,
                                'msg' => 'Feature disabled in demo!!'
                            ];
                return back()->with('status', $output);
            }
        
            DB::beginTransaction();

            $business_id = request()->session()->get('user.business_id');
            $business_name = request()->session()->get('business.name');
            $user_id = request()->session()->get('user.id');
            $package = Package::active()->find($package_id);

            //Call the payment method
            $pay_function = 'pay_' . request()->gateway;
            $payment_transaction_id = null;
            if(method_exists($this, $pay_function)){
                $payment_transaction_id = $this->$pay_function($business_id, $business_name, $package, $request);
            }

            //Add subscription details after payment is succesful
            $this->_add_subscription($business_id, $package, request()->gateway, $payment_transaction_id, $user_id);
            DB::commit();

            $msg = __('lang_v1.success');
            if(request()->gateway == 'offline'){
                $msg = __('superadmin::lang.notification_sent_for_approval');
            }
            $output = ['success' => 1, 'msg' => $msg];

        } catch(\Exception $e){
            DB::rollBack();

            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            $output = ['success' => 0, 'msg' => $e->getMessage()];
        }

        return redirect()
            ->action('\Modules\Superadmin\Http\Controllers\SubscriptionController@index')
            ->with('status', $output);
    }

    /**
     * Stripe payment method
     * @return Response
     */
    protected function pay_stripe($business_id, $business_name, $package, $request){
        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));

        $metadata = ['business_id' => $business_id, 'business_name' => $business_name, 'stripe_email' => $request->stripeEmail, 'package_name' => $package->name];
        // $customer = Customer::create(array(
        //     'email' => $request->stripeEmail,
        //     'source'  => $request->stripeToken,
        //     'metadata' => $metadata
        // ));
        
        $system_currency = System::getCurrency();

        $charge = Charge::create(array(
            'amount'   => $package->price*100,
            'currency' => strtolower($system_currency->code),
            "source" => $request->stripeToken,
            //'customer' => $customer
            'metadata' => $metadata
        ));

        return $charge->id;
    }

    /**
     * Offline payment method
     * @return Response
     */
    protected function pay_offline($business_id, $business_name, $package, $request){

        //Disable in demo
        if (config('app.env') == 'demo') {
            $output = ['success' => 0,
                            'msg' => 'Feature disabled in demo!!'
                        ];
            return back()->with('status', $output);
        }

        //Send notification
        $email = System::getProperty('email');
        $business = Business::find($business_id);
        Notification::route('mail', $email)
            ->notify(new SubscriptionOfflinePaymentActivationConfirmation($business, $package));

        return NULL;
    }

    /**
     * Paypal payment method
     * @return Response
     */
    protected function pay_paypal($business_id, $business_name, $package, $request){

        $provider = new ExpressCheckout();
        $response = $provider->getExpressCheckoutDetails($request->token);

        $token = $request->get('token');
        $PayerID = $request->get('PayerID');
        $invoice_id = $response['INVNUM'];

        // if response ACK value is not SUCCESS or SUCCESSWITHWARNING we return back with error
        if (!in_array(strtoupper($response['ACK']), ['SUCCESS', 'SUCCESSWITHWARNING'])) {
            return back()
                ->with('status', ['success' => 0, 'msg' => 'Something went wrong with paypal transaction']);
        }

        $data = [];
        $data['items'] = [
                [
                    'name' => $package->name,
                    'price' => (float)$package->price,
                    'qty' => 1
                ]
            ];
        $data['invoice_id'] = $invoice_id;
        $data['invoice_description'] = "Order #{$data['invoice_id']} Invoice";
        $data['return_url'] = action('\Modules\Superadmin\Http\Controllers\SubscriptionController@confirm', [$package->id]);
        $data['cancel_url'] = action('\Modules\Superadmin\Http\Controllers\SubscriptionController@pay', [$package->id]);
        $data['total'] = (float)$package->price;

        // if payment is not recurring just perform transaction on PayPal and get the payment status
        $payment_status = $provider->doExpressCheckoutPayment($data, $token, $PayerID);
        $status = isset($payment_status['PAYMENTINFO_0_PAYMENTSTATUS']) ? $payment_status['PAYMENTINFO_0_PAYMENTSTATUS'] : null;

        if(!empty($status) && $status != 'Invalid'){
            return $invoice_id;
        } else {
            $error = 'Something went wrong with paypal transaction';
            throw new Exception($error);
        }
    }

    /**
     * Paypal payment method - redirect to paypal url for payments
     * 
     * @return Response
     */
    public function paypalExpressCheckout(Request $request, $package_id){

        //Disable in demo
        if (config('app.env') == 'demo') {
            $output = ['success' => 0,
                            'msg' => 'Feature disabled in demo!!'
                        ];
            return back()->with('status', $output);
        }

        // Get the cart data or package details.
        $package = Package::active()->find($package_id);

        $data = [];
        $data['items'] = [
                [
                    'name' => $package->name,
                    'price' => (float)$package->price,
                    'qty' => 1
                ]
            ];
        $data['invoice_id'] = str_random(5);
        $data['invoice_description'] = "Order #{$data['invoice_id']} Invoice";
        $data['return_url'] = action('\Modules\Superadmin\Http\Controllers\SubscriptionController@confirm', [$package_id]) . '?gateway=paypal';
        $data['cancel_url'] = action('\Modules\Superadmin\Http\Controllers\SubscriptionController@pay', [$package_id]);
        $data['total'] = (float)$package->price;

        // send a request to paypal 
        // paypal should respond with an array of data
        // the array should contain a link to paypal's payment system
        $system_currency = System::getCurrency();
        $provider = new ExpressCheckout();
        $response = $provider->setCurrency(strtoupper($system_currency->code))->setExpressCheckout($data);

        // if there is no link redirect back with error message
        if (!$response['paypal_link']) {
            return back()
                ->with('status', ['success' => 0, 'msg' => 'Something went wrong with paypal transaction']);
            //For the actual error message dump out $response and see what's in there
        }

        // redirect to paypal
        // after payment is done paypal
        // will redirect us back to $this->expressCheckoutSuccess
        return redirect($response['paypal_link']);
    }

    /**
    * Show the specified resource.
    * @return Response
    */
    public function show($id)
    {
        if (!auth()->user()->can('subscribe')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $subscription = Subscription::where('business_id', $business_id)
                                    ->with(['package', 'created_user', 'business'])
                                    ->find($id);

        $system_settings = System::getProperties([
                'invoice_business_name',
                'email',
                'invoice_business_landmark',
                'invoice_business_city',
                'invoice_business_zip',
                'invoice_business_state',
                'invoice_business_country'
            ]);
        $system = array();
        foreach ($system_settings as $setting) {
           $system[$setting['key']] = $setting['value'];
        }

        return view('superadmin::subscription.show_subscription_modal')
            ->with(compact('subscription', 'system'));
    
    }

    /**
     * Retrieves list of all subscriptions for the current business
     *
     * @return \Illuminate\Http\Response
     */
    public function allSubscriptions(){

        if (!auth()->user()->can('subscribe')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $subscriptions = Subscription::where('subscriptions.business_id', $business_id)
                        ->leftjoin('packages as P', 'subscriptions.package_id',
                            '=', 'P.id')
                        ->leftjoin('users as U', 'subscriptions.created_id',
                            '=', 'U.id')
                        ->addSelect(
                            'P.name as package_name',
                            DB::raw("CONCAT(COALESCE(U.surname, ''), ' ', COALESCE(U.first_name, ''), ' ', COALESCE(U.last_name, '')) as created_by"),
                            'subscriptions.*'
                        );
        return Datatables::of($subscriptions)
             ->editColumn('start_date', 
                    '@if(!empty($start_date)){{@format_date($start_date)}}@endif')
             ->editColumn('end_date', 
                    '@if(!empty($end_date)){{@format_date($end_date)}}@endif')
             ->editColumn('trial_end_date', 
                    '@if(!empty($trial_end_date)){{@format_date($trial_end_date)}}@endif')
             ->editColumn('package_price', 
                    '<span class="display_currency" data-currency_symbol="true">{{$package_price}}</span>')
             ->editColumn('created_at', 
                    '@if(!empty($created_at)){{@format_date($created_at)}}@endif')
             ->filterColumn('created_by', function ($query, $keyword) {
                    $query->whereRaw("CONCAT(COALESCE(U.surname, ''), ' ', COALESCE(U.first_name, ''), ' ', COALESCE(U.last_name, '')) like ?", ["%{$keyword}%"]);
                })
             ->addColumn('action', function($row){
                return '<button type="button" class="btn btn-primary btn-xs btn-modal" data-container=".view_modal" data-href="' . action("\Modules\Superadmin\Http\Controllers\SubscriptionController@show", $row->id) .'" ><i class="fa fa-eye" aria-hidden="true"></i> ' . __("messages.view") . '</button>';
             })
             ->rawColumns(['package_price', 'action'])
             ->make(true);
    }
}
