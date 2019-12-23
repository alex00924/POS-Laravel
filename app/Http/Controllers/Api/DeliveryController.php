<?php
namespace App\Http\Controllers\Api;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\DeliveryInCart;
use App\Http\DataHelper;
use App\Utils\TransactionUtil;

use Illuminate\Support\Str;
use Pusher\Pusher;
use Pusher\PusherException;
use App\BusinessLocation;

class DeliveryController extends Controller
{

    public function order(Request $request)
    {
        $delivery_details = $request->delivery_details;
        $uid = $this->getUid();
        foreach($delivery_details as $key=>$delivery)
        {
            $delivery_details[$key]['uid'] = $uid;
            if($delivery['payment_method'] == 'Cash') {
                $delivery_details[$key]['paid'] = true;
            } else {
                $delivery_details[$key]['paid'] = false;
            }
            $location_id = $delivery['location_id'];
            $delivery_details[$key]['tax_id'] = !empty($delivery_details[$key]['tax_id']) ? $delivery_details[$key]['tax_id'] : null;
            $delivery_details[$key]['created_at'] = \Carbon::now()->toDateTimeString();
            $delivery_details[$key]['updated_at'] = \Carbon::now()->toDateTimeString();
        }

        DeliveryInCart::insert($delivery_details);

        //send notification to web pusher
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
            $data['message'] = 'New Order-'.$delivery_details[0]['location_id'];
            $pusher->trigger(env('PUSHER_CHANNEL'), env('PUSHER_EVENT'), $data);
        } catch (PusherException $e) {
        }

        $email = BusinessLocation::where('id', $location_id)->first()->email;
        if(!empty($email)) {
            $mail_headers = "From: ".env('MAIL_FROM_ADDRESS');
            $subject = 'New order was placed.';
            $message = 'Order id is '.$uid;
            DataHelper::send_mail($email, $subject, $message, $mail_headers);
        }

        $resp = DataHelper::make_resp("success", 200, "successfully ordered");
        $resp['order_uid'] = $uid;
        return $resp;
    }

    public function getUid()
    {
        $uid = DeliveryInCart::orderBy('id', 'desc')->first();
        if(empty($uid))
            return 1;
        else
            return $uid->uid + 1;
    }
}