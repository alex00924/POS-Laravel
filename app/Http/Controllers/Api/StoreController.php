<?php

	namespace App\Http\Controllers\Api;

	use App\Business;
	use App\BusinessLocation;
	use App\Currency;
	use App\Http\DataHelper;
    use App\TaxRate;
    use App\User;
    use Modules\Superadmin\Entities\Subscription;
    use Carbon\Carbon;
    use Illuminate\Http\Request;
	use App\Http\Controllers\Controller;
	use Illuminate\Session\Store;
    use Braintree_Gateway;
    use App\SellingPriceGroup;
    use App\Delivery;
    
	class StoreController extends Controller
	{

		protected $dataHelper;

		public function __construct(DataHelper $dataHelper)
		{
			$this->dataHelper = $dataHelper;
		}

		public function getBusinesses()
		{
            $nType = request()->nType;
            

            $businesses = Business::where('business_type', $nType)
                                ->select('id', 'logo', 'currency_id', 'name', 'is_active', 'owner_id', 'tax_number_1', 'tax_number_2', 'tax_label_1', 'tax_label_2')
                                ->get();
            $active_business = [];
			foreach ($businesses as $business) {

                $stores = BusinessLocation::where('business_id', $business->id)
                                    ->select('id', 'name', 'mobile', 'business_id', 'default_sales_discount', 'braintree_merchant_id', 'braintree_env', 'braintree_public_key', 'braintree_private_key', 'braintree_merchant_account_id')
                                    ->get();
                $active_stores = [];

                if(!$this->dataHelper->validate_data($stores))
                    continue;
                foreach($stores as $store)
                {
                    $tax = TaxRate::where('business_id', $business->id)->select('name', 'amount')->first();
                    $owner = User::find($business->owner_id);

                    $currency = Currency::where('id', $business->currency_id)->select('code', 'symbol')->first();
                    $store['business_name'] = $business->name;
                    $store['is_active'] = $business->is_active;
                    $store['logo'] = $business->logo;
                    $store['business_id'] = $business->id;
                    $store['currency_string'] = $currency->code;
                    $store['currency'] = $currency->symbol;

    //				$store['tax'] = $business->tax_number_1 ? $business->tax_number_1 : ($business->tax_number_2 ? $business->tax_number_2 : 13);
                    $store['tax'] = $tax && $tax->amount ? $tax->amount : 0;
                    $store['tax_type'] = $tax && $tax->name ? $tax->name : 'GST';
    //				$store['tax_type'] = $business->tax_label_1 ? $business->tax_label_1 : ($business->tax_label_2 ? $business->tax_label_2 : 'GST');
                    $store['default_sales_discount'] = $store['default_sales_discount'] ? $store['default_sales_discount'] / 100: 0;  //%
                    
                    $store['merchant_currency'] = 'PKR';
                    
                    if( $store['braintree_env'] != 'none' && $store['braintree_env'] != '' )
                    {
                        
                        ////////////////
                        $gateway = new Braintree_Gateway([
                            'environment' => $store['braintree_env'],
                            'merchantId' => $store['braintree_merchant_id'],
                            'publicKey' => $store['braintree_public_key'],
                            'privateKey' => $store['braintree_private_key']
                        ]);
                        
                        $merchantAccountIterator = $gateway->merchantAccount()->all();

                        foreach($merchantAccountIterator as $merchantAccount) {
                            if( $merchantAccount->id == $store['braintree_merchant_account_id'])
                            {
                                $store['merchant_currency'] = $merchantAccount->currencyIsoCode;
                                break;
                            }
                        }
                    }
                    ///////GET DEFAULT PRICE GROUPS
                    $store['selling_group'] = SellingPriceGroup::select('name')
                                    ->where('location_id', $store['id'] )
                                    ->get()->pluck('name')->toArray();

                    /////////////

                    // hide not subscription business
                    if (!Subscription::active_subscription($store['business_id'])) {
                        continue;
                    }
                    //hide inactive business
                    if ($business->is_active != 1){
                        continue;
                    }
                    //hide superadmin's business
                    if($owner->can('superadmin')) {
                        continue;
                    }

                    $delivery = Delivery::getLocationDelivery($store['business_id'], $store['id']);
                    if(!empty($delivery))
                    {
                        $store['can_delivery'] = true;
                        $store['delivery_area'] = json_decode($delivery->areas);
                        $store['delivery_charge'] = $delivery->charge;
                        $store['delivery_tax_type'] = $delivery->tax_type;
                        $delivery_tax = TaxRate::find($delivery->tax);
                        $store['delivery_tax'] = !empty($delivery_tax) ? $delivery_tax->amount : 0;
                        $store['delivery_is_minimum'] = $delivery->is_minimum;
                        $store['delivery_minimum'] = $delivery->minimum;
                    }
                    $active_stores[] = $store;
                }

                if(!$this->dataHelper->validate_data($active_stores))
                    continue;
                $current_business = [];
                $current_business = (object)["id"=> $business->id, "name"=> $business->name, "logo"=> $business->logo];
                $current_business->locations = $active_stores;
                $active_business[] = $current_business;
			}
			$resp = $this->dataHelper->make_resp("fail", 400, "failure get info");
			$resp['stores'] = null;

			if ($this->dataHelper->validate_data($active_business)) {
				$resp = $this->dataHelper->make_resp("success", 200, "lists of stores");
				$resp['stores'] = $active_business;
			}
			return $resp;
		}

		// get businesses with points remaining ~ Video Rewards panel
		public function getPointsStatus(Request $request) {
		    $app_key = $request->get('app_key');
		    if ($app_key == env('APP_KEY')) {
		        $businesses = Business::where('is_active', 1)
                    ->where('points', '>', 0)
                    ->select('id', 'name', 'points')
                    ->get();
                if($this->dataHelper->validate_data($businesses)) {
                    $resp = $this->dataHelper->make_resp('success', 200, 'success');
                    $resp['data'] = $businesses;
                } else {
                    $resp = $this->dataHelper->make_resp('success', 200, 'no business with remaining points');
                    $resp['data'] = [];
                }
            } else {
                $resp = $this->dataHelper->make_resp('fail', 400, 'Permission denied');
                $resp['data'] = [];
            }
		    return $resp;
        }

        // send emails to Businesses which has points remaining
        public function emailToPointsBusiness(Request $request) {
            $app_key = $request->get('app_key');
            $email_count = 0;
            if ($app_key == env('APP_KEY')) {
                $businesses = Business::where('is_active', 1)
                    ->where('points', '>', 0)
                    ->get();
                if($this->dataHelper->validate_data($businesses)) {
                    foreach ($businesses as $business) {
                        $business_owner = $business->owner;
                        $mail_headers = "From: ".env('APP_NAME') . " - ". env('APP_TITLE')."<".env('MAIL_FROM_ADDRESS').">";
                        $subject = 'Alert: Bullet - Currency Ratio Change';
                        $message = "Hi " . $business->name . ",\r\n" .
                            "We are soon about to change the Bullets Ratio. " .
                            "Our system found that you still have some Bullets left in your account. " .
                            "Kindly redeem or use it before we change the ratio. After that we will not responsible for any loss.\r\n" .
                            "Thanks,\n" .
                            env('APP_NAME') . " - ". env('APP_TITLE');
                        $this->dataHelper->send_mail($business_owner->email, $subject, $message, $mail_headers);
                        $email_count++;
                    }
                }
                $resp = $this->dataHelper->make_resp('success', 200, 'Successfully sent '.$email_count.' emails.');
            } else {
                $resp = $this->dataHelper->make_resp('fail', 400, 'Permission denied');
            }
            return $resp;
        }

        // update point ration and format points of every stores
        public function updatePointRatio(Request $request) {
            $app_key = $request->get('app_key');
            $ratio = $request->get('ratio');
            if ($app_key == env('APP_KEY')) {
                Business::where('points', '>', 0)->update(['points' => 0]);
                $resp = $this->dataHelper->make_resp('success', 200, 'Successfully updated');
            } else {
                $resp = $this->dataHelper->make_resp('fail', 400, 'Permission denied');
            }
            return $resp;
        }

		public function ror(Request $request) {
            $req_dump = print_r($_REQUEST, TRUE);
            $file_name = 'request_'.Carbon::now().'.log';
            $fp = fopen($file_name, 'w');
            fwrite($fp, $req_dump);
            fclose($fp);
            return ["status" => "OK", $_REQUEST];
        }
        public function d_ror(){
		    if(file_exists('request.log')) {
                return response()->download('request.log');
            } else {
                return ["code" => 200, "status" => "OK", "message" => "no log file"];
            }
        }
	}
