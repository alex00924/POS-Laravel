<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Transaction;
use App\Utils\TransactionUtil;
use DB;
use App\User;
use App\BusinessLocation;
use App\Delivery;
use App\DeliveryInCart;
use Yajra\DataTables\Facades\DataTables;
use App\Http\DataHelper;

use Illuminate\Support\Str;
use Pusher\Pusher;
use Pusher\PusherException;

class DeliveryController extends Controller
{
	protected $transactionUtil;
    public function __construct(TransactionUtil $transactionUtil) 
    {
        $this->transactionUtil = $transactionUtil;
    }

    public function newDelivery()
    {
        $business_id = request()->session()->get('user.business_id');
        $business_locations = BusinessLocation::getPermittedLocs($business_id);
        
        if (!Delivery::canDelivery($business_id)) {
            abort(403, 'Unauthorized action.');
        }
        if (request()->ajax()) {
            $new_orders =  DeliveryInCart::leftJoin('business_locations as bl', 'bl.id', '=', 'delivery_in_carts.location_id')  
                                        ->where('delivery_in_carts.state', 0)
                                        ->whereIn('delivery_in_carts.location_id', $business_locations)
                                        ->select(
                                            'delivery_in_carts.created_at as order_date',
                                            'bl.name as business_location',
                                            'delivery_in_carts.uid as order_no',
                                            'user_name',
                                            'user_phone',
                                            'user_email',
                                            'order_area',
                                            'order_area_address',
                                            'payment_method'
                                        );
            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $new_orders->whereIn('delivery_in_carts.location_id', $permitted_locations);
            }
            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end =  request()->end_date;
                $new_orders->whereDate('delivery_in_carts.created_at', '>=', $start)
                            ->whereDate('delivery_in_carts.created_at', '<=', $end);
            }

            $new_orders->groupBy('delivery_in_carts.uid');

            return Datatables::of($new_orders)
                ->addColumn(
                    'action',
                    '<div style="text-align:center"><a onclick="confirm_new_order({{$order_no}}, \'{{$payment_method}}\')" class="btn buttons-collection btn-info btn_action" >{{__(\'delivery.confirm\')}}</a>'.
                    '&nbsp&nbsp<a href="{{ action("DeliveryController@cancel", [$order_no])}}" class="btn btn-danger btn_action" >{{__(\'delivery.cancel\')}}</a><div>'
                )
                ->removeColumn('id')
                ->setRowAttr([
                    'data-href' => function ($row) {
                        return  action('DeliveryController@showNewOrder', [$row->order_no]) ;
                    }])
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('delivery.new_delivery');//, compact('orders', 'is_service_staff', 'service_staff'));
    }

    public function pendingDelivery()
    {
        $business_id = request()->session()->get('user.business_id');
        $business_locations = BusinessLocation::getPermittedLocs($business_id);
        
        if (!Delivery::canDelivery($business_id)) {
            abort(403, 'Unauthorized action.');
        }
        if (request()->ajax()) {
            $new_orders =  DeliveryInCart::leftJoin('business_locations as bl', 'bl.id', '=', 'delivery_in_carts.location_id')  
                                        ->where('delivery_in_carts.state', 3)
                                        ->whereIn('delivery_in_carts.location_id', $business_locations)
                                        ->select(
                                            'delivery_in_carts.created_at as order_date',
                                            'bl.name as business_location',
                                            'delivery_in_carts.uid as order_no',
                                            'user_name',
                                            'user_phone',
                                            'user_email',
                                            'order_area',
                                            'order_area_address',
                                            'paid'
                                        );
            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $new_orders->whereIn('delivery_in_carts.location_id', $permitted_locations);
            }
            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end =  request()->end_date;
                $new_orders->whereDate('delivery_in_carts.created_at', '>=', $start)
                            ->whereDate('delivery_in_carts.created_at', '<=', $end);
            }

            $new_orders->groupBy('delivery_in_carts.uid');

            return Datatables::of($new_orders)
                ->addColumn(
                    'action',
                    '<div style="text-align:center"> @if($paid) <a href="{{ action("DeliveryController@confirmPending", [$order_no])}}" class="btn buttons-collection btn-info btn_action" >{{__(\'delivery.complete\')}}</a>'.
                    '&nbsp&nbsp @endif <a href="{{ action("DeliveryController@cancelPending", [$order_no])}}" class="btn btn-danger btn_action" >{{__(\'delivery.cancel\')}}</a><div>'
                )
                ->removeColumn('id')
                ->setRowAttr([
                    'data-href' => function ($row) {
                        return  action('DeliveryController@showNewOrder', [$row->order_no]) ;
                    }])
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('delivery.pending_delivery');//, compact('orders', 'is_service_staff', 'service_staff'));
    }
    
    public function completeDelivery()
    {
        if (!auth()->user()->can('access_delivery') ) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $sells = Transaction::leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
                ->leftJoin('transaction_payments as tp', 'transactions.id', '=', 'tp.transaction_id')
                ->join(
                    'business_locations AS bl',
                    'transactions.location_id',
                    '=',
                    'bl.id'
                )
	            ->where('transactions.delivery_uid', '!=', null)
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final')
                ->select(
                    'transactions.id',
                    'transaction_date',
                    'is_direct_sale',
                    'invoice_no',
                    'contacts.name',
                    'payment_status',
                    'final_total',
                    'tp.uid as uid',
                    DB::raw('SUM(IF(tp.is_return = 1,-1*tp.amount,tp.amount)) as total_paid'),
                    'bl.name as business_location'
                );

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $sells->whereIn('transactions.location_id', $permitted_locations);
            }

            //Add condition for created_by,used in sales representative sales report
            if (request()->has('created_by')) {
                $created_by = request()->get('created_by');
                if (!empty($created_by)) {
                    $sells->where('transactions.created_by', $created_by);
                }
            }

            //Add condition for location,used in sales representative expense report
            if (request()->has('location_id')) {
                $location_id = request()->get('location_id');
                if (!empty($location_id)) {
                    $sells->where('transactions.location_id', $location_id);
                }
            }

            if (!empty(request()->customer_id)) {
                $customer_id = request()->customer_id;
                $sells->where('contacts.id', $customer_id);
            }
            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end =  request()->end_date;
                $sells->whereDate('transaction_date', '>=', $start)
                            ->whereDate('transaction_date', '<=', $end);
            }

            //Check is_direct sell
            if (request()->has('is_direct_sale')) {
                $is_direct_sale = request()->is_direct_sale;
                if ($is_direct_sale == 0) {
                    $sells->where('is_direct_sale', 0);
                }
            }

            //Add condition for commission_agent,used in sales representative sales with commission report
            if (request()->has('commission_agent')) {
                $commission_agent = request()->get('commission_agent');
                if (!empty($commission_agent)) {
                    $sells->where('transactions.commission_agent', $commission_agent);
                }
            }
            $sells->groupBy('transactions.id');

            return Datatables::of($sells)
                ->addColumn(
                    'action',
                    '<div class="btn-group">
                    <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                        data-toggle="dropdown" aria-expanded="false">' .
                        __("messages.actions") .
                        '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-right" role="menu">
                    @if(auth()->user()->can("sell.view") || auth()->user()->can("direct_sell.access") )
                        <li><a href="#" data-href="{{action(\'SellController@show\', [$id])}}" class="btn-modal" data-container=".view_modal" data-uid="{{$uid}}"><i class="fa fa-external-link" aria-hidden="true"></i> @lang("messages.view")</a></li>
                    @endif
                    @if($is_direct_sale == 0)
                        @can("sell.update")
                        <li><a target="_blank" href="{{action(\'SellPosController@edit\', [$id])}}" data-uid="{{$uid}}"><i class="glyphicon glyphicon-edit"></i> @lang("messages.edit")</a></li>
                        @endcan
                        @else
                        @can("direct_sell.access")
                            <li><a target="_blank" href="{{action(\'SellController@edit\', [$id])}}" data-uid="{{$uid}}"><i class="glyphicon glyphicon-edit"></i> @lang("messages.edit")</a></li>
                        @endcan
                    @endif
                    @can("sell.delete")
                    <li><a href="{{action(\'SellPosController@destroy\', [$id])}}" class="delete-sale" data-uid="{{$uid}}"><i class="fa fa-trash"></i> @lang("messages.delete")</a></li>
                    @endcan

                    @if(auth()->user()->can("sell.view") || auth()->user()->can("direct_sell.access") )
                        <li><a href="#" class="print-invoice" data-href="{{route(\'sell.printInvoice\', [$id])}}" data-uid="{{$uid}}"><i class="fa fa-print" aria-hidden="true"></i> @lang("messages.print")</a></li>
                    @endif
                    
                    <li class="divider"></li> 
                    @if($payment_status != "paid")
                        @if(auth()->user()->can("sell.create") || auth()->user()->can("direct_sell.access") )
                            <li><a href="{{action(\'TransactionPaymentController@addPayment\', [$id])}}" class="add_payment_modal"><i class="fa fa-money"></i> @lang("purchase.add_payment")</a></li>
                        @endif
                    @endif
                        <li><a href="{{action(\'TransactionPaymentController@show\', [$id])}}" class="view_payment_modal"><i class="fa fa-money"></i> @lang("purchase.view_payments")</a></li>
                    @can("sell.create")
                        <li><a href="{{action(\'SellController@duplicateSell\', [$id])}}"><i class="fa fa-copy"></i> @lang("lang_v1.duplicate_sell")</a></li>
                    @endcan
                    </ul></div>'
                )
                ->removeColumn('id')
                ->editColumn(
                    'final_total',
                    '<span class="display_currency final-total" data-currency_symbol="true" data-orig-value="{{$final_total}}">{{$final_total}}</span>'
                )
                ->editColumn(
                    'total_paid',
                    '<span class="display_currency total-paid" data-currency_symbol="true" data-orig-value="{{$total_paid}}">{{$total_paid}}</span>'
                )
                ->editColumn('transaction_date', '{{@format_date($transaction_date)}}')
                ->editColumn(
                    'payment_status',
                    '<a href="{{ action("TransactionPaymentController@show", [$id])}}" class="view_payment_modal payment-status-label" data-orig-value="{{$payment_status}}" data-status-name="{{__(\'lang_v1.\' . $payment_status)}}"><span class="label @payment_status($payment_status)">{{__(\'lang_v1.\' . $payment_status)}}</span></a>'
                )
                 ->addColumn('total_remaining', function ($row) {
                    $total_remaining =  $row->final_total - $row->total_paid;
                    return '<span data-orig-value="' . $total_remaining . '" class="display_currency total-remaining" data-currency_symbol="true">' . $total_remaining . '</span>';
                 })
                ->setRowAttr([
                    'data-href' => function ($row) {
                        if (auth()->user()->can("sell.view")) {
                            return  action('SellController@show', [$row->id]) ;
                        } else {
                            return '';
                        }
                    }])
                ->rawColumns(['final_total', 'action', 'total_paid', 'total_remaining', 'payment_status', 'invoice_no'])
                ->make(true);
        }
        return view('delivery.complete_delivery');
    }
    
    public function confirmPending($uid)
    {
         //ping confirm message to app 
         try {
            $options = array(
                'cluster' => env('PUSHER_CLUSTER'),
                'useTLS' => env('PUSHER_USETLS')
            );
            $pusher = new Pusher(
                env('PUSHER_APP_KEY'),
                env('PUSHER_APP_SECRET'),
                env('PUSHER_APP_ID'),
                $options
            );
            $data['message'] = 'Confirm pending order-'.$uid;
            $pusher->trigger(env('PUSHER_CHANNEL'), env('PUSHER_EVENT'), $data);
        } catch (PusherException $e) {
        }

        $deliverys = DeliveryInCart::where('uid', $uid);
        $deliverys->update(['state' => 1]);

        $new_orders = $deliverys->get()->toArray();
        $total_price = 0;
        $total_payable = 0;
        $total_tax = 0;
        $total_discount = 0;
        $delivery_price = $new_orders[0]['delivery_price']; 
        foreach($new_orders as $new_order)
        {
            $total_price += $new_order['sub_total'];
            $total_discount += $new_order['discount'] * $new_order['product_quantity'];
            $total_tax += $new_order['tax'] * $new_order['product_quantity'];
        }
        $total_tax += $new_orders[0]['delivery_tax'];
        $total_payable = $total_price + $delivery_price + $total_tax - $total_discount;

        ////////////////////////In the case of cash payment, add this delivery to transaction automatically///////////////////////////////////////
		$this->transactionUtil->createTransactionFromDelivery($new_orders, $uid);
        ///////////////////////////////////////////////////////////////

        $mail_headers = "From: ".env('APP_NAME') . " - ". env('APP_TITLE')."<".env('MAIL_FROM_ADDRESS').">";
        $subject = 'Alert: Bullet - Delivery Completed';

        $message = "Hi " . $new_orders[0]['user_name'] . ",\r\n" .
            "Your order has been delivered to you \r\n" .
            env('APP_NAME') . " - ". env('APP_TITLE');
        DataHelper::send_mail($new_orders[0]['user_email'], $subject, $message, $mail_headers);
        return back()
                ->with('status', ['success' => 1,
                    'msg' => __('messages.delivery_complete')]);
    }

    public function confirm($uid)
    {
         //ping confirm message to app 
         try {
            $options = array(
                'cluster' => env('PUSHER_CLUSTER'),
                'useTLS' => env('PUSHER_USETLS')
            );
            $pusher = new Pusher(
                env('PUSHER_APP_KEY'),
                env('PUSHER_APP_SECRET'),
                env('PUSHER_APP_ID'),
                $options
            );
            $data['message'] = 'Confirm order-'.$uid;
            $pusher->trigger(env('PUSHER_CHANNEL'), env('PUSHER_EVENT'), $data);
        } catch (PusherException $e) {
        }

        $deliverys = DeliveryInCart::where('uid', $uid);
        $deliverys->update(['state' => 3]);
        $mail_headers = "From: ".env('APP_NAME') . " - ". env('APP_TITLE')."<".env('MAIL_FROM_ADDRESS').">";
        $subject = 'Alert: Bullet - Delivery Confirmed';
        
        $new_orders = $deliverys->get()->toArray();
        $total_price = 0;
        $total_payable = 0;
        $total_tax = 0;
        $total_discount = 0;
        $delivery_price = $new_orders[0]['delivery_price']; 
        foreach($new_orders as $new_order)
        {
            $total_price += $new_order['sub_total'];
            $total_discount += $new_order['discount'] * $new_order['product_quantity'];
            $total_tax += $new_order['tax'] * $new_order['product_quantity'];
        }
        $total_tax += $new_orders[0]['delivery_tax'];
        $total_payable = $total_price + $delivery_price + $total_tax - $total_discount;

        ////////// increase points when cash pay//////////
        if($new_orders[0]['payment_method'] == 'Cash') {
            $this->transactionUtil->increasePointsFromDelivery($new_orders, $uid);
        }
        /////////////////////////////

        $message = "Hi " . $new_orders[0]['user_name'] . ",\r\n" .
            "Your delivery confirmed. \r\n" .
            env('APP_NAME') . " - ". env('APP_TITLE');
        DataHelper::send_mail($new_orders[0]['user_email'], $subject, $message, $mail_headers);
        return back()
                ->with('status', ['success' => 1,
                    'msg' => __('messages.delivery_confirm')]);
    }
    
    public function cancelPending($uid)
    {
         //ping cancel message to app 
         try {
            $options = array(
                'cluster' => env('PUSHER_CLUSTER'),
                'useTLS' => env('PUSHER_USETLS')
            );
            $pusher = new Pusher(
                env('PUSHER_APP_KEY'),
                env('PUSHER_APP_SECRET'),
                env('PUSHER_APP_ID'),
                $options
            );
            $data['message'] = 'Cancel pending order-'.$uid;
            $pusher->trigger(env('PUSHER_CHANNEL'), env('PUSHER_EVENT'), $data);
        } catch (PusherException $e) {
        }

        $deliverys = DeliveryInCart::where('uid', $uid);
        $deliverys->update(['state' => 2]);
        $mail_headers = "From: ".env('APP_NAME') . " - ". env('APP_TITLE')."<".env('MAIL_FROM_ADDRESS').">";
        $subject = 'Alert: Bullet - Delivery Confirmed';
        
        $new_orders = $deliverys->get()->toArray();
        $total_price = 0;
        $total_payable = 0;
        $total_tax = 0;
        $total_discount = 0;
        $delivery_price = $new_orders[0]['delivery_price']; 
        foreach($new_orders as $new_order)
        {
            $total_price += $new_order['sub_total'];
            $total_discount += $new_order['discount'] * $new_order['product_quantity'];
            $total_tax += $new_order['tax'] * $new_order['product_quantity'];
        }
        $total_tax += $new_orders[0]['delivery_tax'];
        $total_payable = $total_price + $delivery_price + $total_tax - $total_discount;

        $message = "Hi " . $new_orders[0]['user_name'] . ",\r\n" .
            "Unfortunately you were unable to receive the order. \r\n" .
            "Delivery area: " . $new_orders[0]['order_area_address'] . "\r\n" .
            "Total price: " . $total_price . "\r\n" .
            "Total tax: " . $total_tax . "\r\n" . 
            "Total payable: " . $total_payable . "\r\n" . 
            "Thanks,\n" .
            env('APP_NAME') . " - ". env('APP_TITLE');

        DataHelper::send_mail($new_orders[0]['user_email'], $subject, $message, $mail_headers);

        ///////////decrease points///////////////
        $this->transactionUtil->decreasePointsFromDelivery($new_orders, $uid);
       /////////////////////////////////////////
        return back()
                ->with('status', ['success' => 1,
                    'msg' => __('messages.delivery_cancel')]);
    }

    public function cancel($uid)
    {
         //ping cancel message to app 
         try {
            $options = array(
                'cluster' => env('PUSHER_CLUSTER'),
                'useTLS' => env('PUSHER_USETLS')
            );
            $pusher = new Pusher(
                env('PUSHER_APP_KEY'),
                env('PUSHER_APP_SECRET'),
                env('PUSHER_APP_ID'),
                $options
            );
            $data['message'] = 'Cancel order-'.$uid;
            $pusher->trigger(env('PUSHER_CHANNEL'), env('PUSHER_EVENT'), $data);
        } catch (PusherException $e) {
        }

        $deliverys = DeliveryInCart::where('uid', $uid);
        $deliverys->update(['state' => 2]);
        $mail_headers = "From: ".env('APP_NAME') . " - ". env('APP_TITLE')."<".env('MAIL_FROM_ADDRESS').">";
        $subject = 'Alert: Bullet - Delivery Canceled';
        
        $new_orders = $deliverys->get()->toArray();
        $total_price = 0;
        $total_payable = 0;
        $total_tax = 0;
        $total_discount = 0;
        $delivery_price = $new_orders[0]['delivery_price']; 
        foreach($new_orders as $new_order)
        {
            $total_price += $new_order['sub_total'];
            $total_discount += $new_order['discount'] * $new_order['product_quantity'];
            $total_tax += $new_order['tax'] * $new_order['product_quantity'];
        }
        $total_tax += $new_orders[0]['delivery_tax'];
        $total_payable = $total_price + $delivery_price + $total_tax - $total_discount;

        $message = "Hi " . $new_orders[0]['user_name'] . ",\r\n" .
            "Your delivery canceled. \r\n" .
            "Delivery area: " . $new_orders[0]['order_area_address'] . "\r\n" .
            "Total price: " . $total_price . "\r\n" .
            "Total tax: " . $total_tax . "\r\n" . 
            "Total payable: " . $total_payable . "\r\n" . 
            "Thanks,\n" .
            env('APP_NAME') . " - ". env('APP_TITLE');

        DataHelper::send_mail($new_orders[0]['user_email'], $subject, $message, $mail_headers);
       
        return back()
                ->with('status', ['success' => 1,
                    'msg' => __('messages.delivery_cancel')]);
    }

    //show new Order items
    public function showNewOrder($uid)
    {
        $new_orders = DeliveryInCart::where('uid', $uid)->get()->toArray();
        $total_price = 0;
        $total_payable = 0;
        $total_tax = 0;
        $total_discount = 0;
        $delivery_price = $new_orders[0]['delivery_price']; 
        $points = $new_orders[0]['points'];
        foreach($new_orders as $new_order)
        {
            $total_price += $new_order['sub_total'];
            $total_discount += $new_order['discount'] * $new_order['product_quantity'];
            $total_tax += $new_order['tax'] * $new_order['product_quantity'];
        }
        $total_tax += $new_orders[0]['delivery_tax'];
        $total_payable = $total_price + $delivery_price + $total_tax - $total_discount - $points;

        return view('delivery.show')
                ->with(compact('new_orders', 'total_price', 'total_tax', 'total_discount', 'total_payable', 'delivery_price'));
    }

    public function uncompleteDelivery()
    {
        $business_id = request()->session()->get('user.business_id');
        $business_locations = BusinessLocation::getPermittedLocs($business_id);
        
        if (!Delivery::canDelivery($business_id)) {
            abort(403, 'Unauthorized action.');
        }
        if (request()->ajax()) {
            $new_orders =  DeliveryInCart::leftJoin('business_locations as bl', 'bl.id', '=', 'delivery_in_carts.location_id')  
                                        ->where('delivery_in_carts.state', 2)
                                        ->whereIn('delivery_in_carts.location_id', $business_locations)
                                        ->select(
                                            'delivery_in_carts.created_at as order_date',
                                            'bl.name as business_location',
                                            'delivery_in_carts.uid as order_no',
                                            'user_name',
                                            'user_phone',
                                            'user_email',
                                            'order_area',
                                            'order_area_address'
                                        );
            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $new_orders->whereIn('delivery_in_carts.location_id', $permitted_locations);
            }
            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end =  request()->end_date;
                $new_orders->whereDate('delivery_in_carts.created_at', '>=', $start)
                            ->whereDate('delivery_in_carts.created_at', '<=', $end);
            }

            $new_orders->groupBy('delivery_in_carts.uid');

            return Datatables::of($new_orders)
                ->removeColumn('id')
                ->setRowAttr([
                    'data-href' => function ($row) {
                        return  action('DeliveryController@showNewOrder', [$row->order_no]) ;
                    }])
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('delivery.uncomplete_delivery');
    }

}