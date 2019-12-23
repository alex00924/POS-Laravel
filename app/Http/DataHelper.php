<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 8/29/2018
 * Time: 1:39 AM
 */

namespace App\Http;

use Mail;
use App\Business;
use App\ProductInCart;
use App\RewardedPoint;
use App\TransactionPayment;
use Illuminate\Support\Str;
use Pusher\Pusher;
use Pusher\PusherException;
use App\BusinessLocation;

class DataHelper
{
    public static function validate_data($data) {
        return $data && count($data) > 0;
    }
    public static function make_resp($status, $code, $message, $amount=null) {
        return compact('status', 'code', 'message', 'amount');
    }
    public static function get_uid() {
        $characters1 = 'abcdefghijkmnpqrstuvwxyz';
        $characters2 = 'abcdefghijkmnpqrstuvwxyz';
        $characters3 = 'abcdefghijkmnpqrstuvwxyz';
        do
        {
            $token = mt_rand(10, 99);
            $str1 = $characters1[rand(0, strlen($characters1) - 1)];
            $str2 = $characters2[rand(0, strlen($characters2) - 1)];
            $str3 = $characters3[rand(0, strlen($characters3) - 1)];
            $code = $str1.$str2.$token . substr(strftime("%Y", time()),2) . $str3;
            $user_code = ProductInCart::where('uid', $code)->get();
        } while(!$user_code);
        return $code;
    }

	/**
	 * generate unique id in transaction payment
	 * @return string
	 */
	public static function get_payment_uid() {
		$characters1 = 'abcdefghijkmnpqrstuvwxyz';
		$characters2 = 'abcdefghijkmnpqrstuvwxyz';
		do
		{
			$token = mt_rand(10, 99);
			$str1 = $characters1[rand(0, strlen($characters1) - 1)];
			$str2 = $characters2[rand(0, strlen($characters2) - 1)];
			$code = $str1.$str2.$token . substr(strftime("%Y", time()),2);
			$user_code = TransactionPayment::where('uid', $code)->get();
		} while(!$user_code);
		return $code;
	}

    public static function send_pusher($uid, $transaction_id) {
//        ProductInCart::where('uid', $uid)
//            ->delete();
        $result = RewardedPoint::where('cart_uid', $uid)
            ->update(['transaction_id' =>  $transaction_id, 'purchased' => true]);
        ProductInCart::where('uid', $uid)
            ->update(['purchased' => true]);
        $used_points = RewardedPoint::where('cart_uid', $uid)->where('purchased', true)
            ->select('location_id', 'points')->first();
        /**Add points to current business*/
        BusinessLocation::where('id', $used_points['location_id'])->increment('points', $used_points['points']);

        $options = array(
            'cluster' => env('PUSHER_CLUSTER'),
            'useTLS' => env('PUSHER_USETLS')
        );
        try {
            $pusher = new Pusher(
                env('PUSHER_APP_KEY'),
                env('PUSHER_APP_SECRET'),
                env('PUSHER_APP_ID'),
                $options
            );
            $data['message'] = $uid;
            if($result >= 0) {
                $pusher->trigger(env('PUSHER_CHANNEL'), env('PUSHER_EVENT'), $data);
            }
        } catch (PusherException $e) {
        }

    }

    //Cancel products in mobile cart
    public static function cancel_products_pusher($uid) {
        $options = array(
            'cluster' => env('PUSHER_CLUSTER'),
            'useTLS' => env('PUSHER_USETLS')
        );
        try {
            $pusher = new Pusher(
                env('PUSHER_APP_KEY'),
                env('PUSHER_APP_SECRET'),
                env('PUSHER_APP_ID'),
                $options
            );
            $data['message'] =env('PUSHER_CANCEL_EVENT').'-'.$uid;
            $pusher->trigger(env('PUSHER_CHANNEL'), env('PUSHER_EVENT'), $data);
        } catch (PusherException $e) {
        }
    }

    //Check app connection
    public static function ping_to_app($uid) {
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
            $data['message'] = env('PUSHER_PING_EVENT').'-'.$uid;
            $pusher->trigger(env('PUSHER_CHANNEL'), env('PUSHER_EVENT'), $data);
        } catch (PusherException $e) {
        }
    }
    //Send connection result to frontend
    public static function pong_from_app($event) {
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
            $data['message'] = $event;
            $pusher->trigger(env('PUSHER_CHANNEL'), env('PUSHER_EVENT'), $data);
            echo $event;
        } catch (PusherException $e) {
        }
    }

    public static function send_mail($to, $subject, $message_content, $headers) {
        Mail::send([],[], function ($message) use($to, $subject, $message_content, $headers) {
			$message->from(env('MAIL_FROM_ADDRESS'), $headers);
			$message->to($to);
            $message->subject($subject);
            $message->setBody($message_content);
 		});
//        return mail($to, $subject, $message, $headers);
    }

    public static function get_currency_rate($source_currency, $target_currency = 'USD', $amount = 1){
	    $response = file_get_contents("http://apilayer.net/api/live?access_key=27bb6c27a91ca59d7a9a17cd6bd3d62f&currencies=$source_currency&source=$target_currency&format=1");
	    if (str_contains($response, 'error')) {
	        return null;
        } else {
            $response = explode(':', $response);
            $response = $response[count($response) - 1];
            $usd2currency = trim(explode('}', $response)[0]);
            $currency2usd = number_format(1 / $usd2currency, 4);
            return number_format($amount * $currency2usd, 5);
        }
    }
}