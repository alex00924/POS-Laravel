<?php

	namespace App\Http\Controllers\Api;

	use App\Business;
	use App\Http\DataHelper;
	use App\ProductInCart;
    use App\RewardedPoint;
    use App\TaxRate;
    use App\Transaction;
	use App\TransactionPayment;
	use App\Utils\BusinessUtil;
	use App\Utils\CashRegisterUtil;
	use App\Utils\ContactUtil;
	use App\Utils\ModuleUtil;
	use App\Utils\ProductUtil;
	use App\Utils\TransactionUtil;
	use App\Utils\Util;
	use Braintree;
	use Braintree\ClientToken;
    use Braintree_Gateway;
    use Braintree_Transaction;
    use Braintree_Configuration;
	use Illuminate\Http\Request;
	use App\Http\Controllers\Controller;
	use Illuminate\Support\Facades\DB;
    use Symfony\Component\VarDumper\Cloner\Data;

    use App\BusinessLocation;
    use App\DeliveryInCart;
    
    class PaymentsController extends Controller
	{

		/**
		 * All Utils instance.
		 *
		 */
		protected $contactUtil;
		protected $productUtil;
		protected $businessUtil;
		protected $transactionUtil;
		protected $cashRegisterUtil;
		protected $moduleUtil;
		protected $util;

		/**
		 * Constructor
		 *
		 * @param ProductUtils $product
		 * @return void
		 */
		public function __construct(
			ContactUtil $contactUtil,
			ProductUtil $productUtil,
			BusinessUtil $businessUtil,
			TransactionUtil $transactionUtil,
			CashRegisterUtil $cashRegisterUtil,
			ModuleUtil $moduleUtil,
			Util $util
		) {

			$this->contactUtil = $contactUtil;
			$this->productUtil = $productUtil;
			$this->businessUtil = $businessUtil;
			$this->transactionUtil = $transactionUtil;
			$this->cashRegisterUtil = $cashRegisterUtil;
			$this->moduleUtil = $moduleUtil;
			$this->util = $util;

			$this->dummyPaymentLine = ['method' => 'cash', 'amount' => 0, 'note' => '', 'card_transaction_number' => '', 'card_number' => '', 'card_type' => '', 'card_holder_name' => '', 'card_month' => '', 'card_year' => '', 'card_security' => '', 'cheque_number' => '', 'bank_account_number' => '',
				'is_return' => 0, 'transaction_no' => ''];
		}

		/**
		 * generate brain tree toke to get nonce with this
		 * @return array
		 */
		public function generate_token($business_loc_id)
		{
            /* Get Braintree Info */
            $braintree_env = (BusinessLocation::find($business_loc_id))['braintree_env'];
            $braintree_merchant_id = (BusinessLocation::find($business_loc_id))['braintree_merchant_id'];
            $braintree_public_key = (BusinessLocation::find($business_loc_id))['braintree_public_key'];
            $braintree_private_key = (BusinessLocation::find($business_loc_id))['braintree_private_key'];
            $braintree_merchant_account_id = (BusinessLocation::find($business_loc_id))['braintree_merchant_account_id'];

            if ($braintree_env == 'none' || $braintree_env == NULL) {
                $resp = DataHelper::make_resp("success", 201, null);
                return $resp;
            }

            $resp = null;
            try {
                /* Set Brain Configuration with current bussiness store */
                Braintree_Configuration::environment($braintree_env);
                Braintree_Configuration::merchantId($braintree_merchant_id);
                Braintree_Configuration::publicKey($braintree_public_key);
                Braintree_Configuration::privateKey($braintree_private_key);

                $clientToken = ClientToken::generate();
                $minAmount = (BusinessLocation::find($business_loc_id))['minimum_amount'];
                if(empty($minAmount))
                    $minAmount = 0;
                $resp = DataHelper::make_resp("success", 201, $clientToken, $minAmount);
                //Check if Braintree is configured or not
                if(($braintree_merchant_account_id && $braintree_merchant_id && $braintree_private_key && $braintree_public_key)){

                    $resp['code'] = 200;//200=> card payable, 201=>don't use card payment
                }
            } catch (\Exception $e) {
                $resp = DataHelper::make_resp("success", 201, null);
            }
			
            /* Set Brain Configuration with basic info */
            Braintree_Configuration::environment(env('BRAINTREE_ENV'));
            Braintree_Configuration::merchantId(env('BRAINTREE_MERCHANT_ID'));
            Braintree_Configuration::publicKey(env('BRAINTREE_PUBLIC_KEY'));
            Braintree_Configuration::privateKey(env('BRAINTREE_PRIVATE_KEY'));
			return $resp;

		}

		/**
		 * Process payment on mobile by credit card
		 * @param Request $request
		 * @return array
		 */
		public function process(Request $request)
		{
			$payload = $request->input('payload', false);
			$nonce = $request->get('nonce');
			$currency = $request->get('currency');
			$cardType = $request->get('cardType');
			$amount = $request->get('amount'); // don't know which type of currency so have to convert to usd
			$amount_for_braintree = $request->get('amount_for_braintree'); // don't know which type of currency so have to convert to usd
            $type = $request->get('type');

            $resp = DataHelper::make_resp("fail", 400, "payment failed");
            $resp['transaction'] = null;
            if (!is_null($amount)) {
                $braintree_env = (BusinessLocation::find($request->get('location_id')))['braintree_env'];
                $braintree_merchant_id = (BusinessLocation::find($request->get('location_id')))['braintree_merchant_id'];
                $braintree_public_key = (BusinessLocation::find($request->get('location_id')))['braintree_public_key'];
                $braintree_private_key = (BusinessLocation::find($request->get('location_id')))['braintree_private_key'];
                /* Set Brain Configuration with current bussiness store */
                Braintree_Configuration::environment($braintree_env);
                Braintree_Configuration::merchantId($braintree_merchant_id);
                Braintree_Configuration::publicKey($braintree_public_key);
                Braintree_Configuration::privateKey($braintree_private_key);
                $merchantID = (BusinessLocation::find($request->get('location_id')))['braintree_merchant_account_id'];
                $discount = (BusinessLocation::find($request->get('location_id')))['default_sales_discount'];
                $discount_rate = $discount / 100;

                $status = Braintree_Transaction::sale([
                    'amount' => $amount_for_braintree,
                    'paymentMethodNonce' => $nonce,
                    'merchantAccountId' => $merchantID,
                    'options' => [
                        'submitForSettlement' => True
                    ]
                ]);

                $result = response()->json($status);
                
                if ($result->original->success) {
                    $sub_toal = 0;
            
                    foreach ($request->get("products") as $product) {
                        $sub_toal += $product['unit_price'] * $product['quantity'];
                    }

                    $business_id = $request->get('business_id');
                    $location_id = $request->get('location_id');
                    $points = $request->get('points');
                    $point_ratio = $request->get('point_ratio');
                    $total_price = $request->get('amount');
                    $business_tax_info = TaxRate::where('business_id', $business_id)->select('name', 'amount')->first();
                    $tax = $business_tax_info && $business_tax_info->amount ? $business_tax_info->amount : 0;

                    $tax = $sub_toal * (1 - $discount_rate ) * $tax / 100;

                    $sell_price_tax = (BusinessLocation::find($location_id))['sell_price_tax'];//business id
                    $price_group = 0;//selling_price_group, business_id
                    $tax_calculation_amount = 0.00;
                    $shipping_charges = 0.00;
                    $change_return = 0.00;
                    $status = "final";
                    $tax_rate_id = null;

                    $input = $request->all();
                    $input['sell_price_tax'] = $sell_price_tax;
                    $input['price_group'] = $price_group;
                    $input['tax_calculation_amount'] = $tax_calculation_amount;
                    $input['shipping_charges'] = $shipping_charges;
                    $input['final_total'] = $amount;
                    $input['change_return'] = $change_return;
                    $input['status'] = $status;
                    if($request->get('is_delivery'))
                        $input['delivery_uid'] = $request->get('delivery_uid');
                        
                    if (!empty($input['products'])) {

                        $invoice_total = ['total_before_tax' => $sub_toal, 'tax' => $tax, 'discount' => $discount, 'final_total' => $amount, 'points' => $points];
                        DB::beginTransaction();

                        if (empty($request->input('transaction_date'))) {
                            $input['transaction_date'] =  \Carbon::now();
                        } else {
                            $input['transaction_date'] = $this->productUtil->uf_date($request->input('transaction_date'));
                        }

                        $uid = DataHelper::get_payment_uid();

                        $transaction = $this->transactionUtil->createMobileCardSellTransaction($business_id, $input, $invoice_total);

                        //Check for final and do some processing.
                        if ($input['status'] == 'final') {
                            //update product stock
                            foreach ($input['products'] as $product) {
                                if ($product['enable_stock']) {
                                    $this->productUtil->decreaseProductQuantity(
                                        $product['product_id'],
                                        $product['variation_id'],
                                        $input['location_id'],
                                        $this->productUtil->num_uf($product['quantity'])
                                    );
                                }
                            }
                        }

                        $this->transactionUtil->createOrUpdateSellLines($transaction, $input['products'], $input['location_id']);

                        $prefix_type = 'sell_payment';
                        $ref_count = $this->util->setAndGetReferenceCount($prefix_type, $business_id);
                        $payment_ref_no = $this->util->generateReferenceNumber($prefix_type, $ref_count, $business_id);

                        TransactionPayment::create([
                            'transaction_id' => $transaction->id,
                            'amount' => $transaction->final_total,
                            'method' => 'mobile',
                            'paid_on' => \Carbon::now()->toDateTimeString(),
                            'created_by' => null,
                            'card_type' => $cardType,
                            'payment_ref_no' => $payment_ref_no,
                            'uid' => $uid,

                        ]);

                        $transaction->save();
                        //Update payment status
                        // $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);

                         //Allocate the quantity from purchase and add mapping of
                        //purchase & sell lines in
                        //transaction_sell_lines_purchase_lines table
                        $business = ['id' => $business_id,
                            'accounting_method' => "FIFO",
                            'location_id' => $input['location_id']
                        ];
                        $this->transactionUtil->mapPurchaseSell($business, $transaction->sell_lines, 'purchase');
                        
                        
                        DB::commit();

                        $cart_uid = 'ccard-'.(DataHelper::get_uid());
                        RewardedPoint::create(compact('business_id', 'location_id', 'points', 'point_ratio', 'total_price', 'cart_uid'));
                        RewardedPoint::where('cart_uid', $cart_uid)
                            ->update(['transaction_id' => $transaction->id, 'purchased' => true]);
                        /**Add points to current business*/
                        BusinessLocation::where('id', $location_id)->increment('points', $points);
                    }

                    $resp = DataHelper::make_resp("success", 200, "payment success");
                    $resp['transaction'] = $transaction;
                }
            }

            /* Set Brain Configuration with basic info */
            Braintree_Configuration::environment(env('BRAINTREE_ENV'));
            Braintree_Configuration::merchantId(env('BRAINTREE_MERCHANT_ID'));
            Braintree_Configuration::publicKey(env('BRAINTREE_PUBLIC_KEY'));
            Braintree_Configuration::privateKey(env('BRAINTREE_PRIVATE_KEY'));
			return $resp;
		}

        public function processForDelivery(Request $request)
		{
			$nonce = $request->get('nonce');
			$amount_for_braintree = $request->get('amount_for_braintree'); // don't know which type of currency so have to convert to usd

            $resp = DataHelper::make_resp("fail", 400, "payment failed");
            if (!is_null($amount_for_braintree)) {
                $braintree_env = (BusinessLocation::find($request->get('location_id')))['braintree_env'];
                $braintree_merchant_id = (BusinessLocation::find($request->get('location_id')))['braintree_merchant_id'];
                $braintree_public_key = (BusinessLocation::find($request->get('location_id')))['braintree_public_key'];
                $braintree_private_key = (BusinessLocation::find($request->get('location_id')))['braintree_private_key'];
                /* Set Brain Configuration with current bussiness store */
                Braintree_Configuration::environment($braintree_env);
                Braintree_Configuration::merchantId($braintree_merchant_id);
                Braintree_Configuration::publicKey($braintree_public_key);
                Braintree_Configuration::privateKey($braintree_private_key);
                $merchantID = (BusinessLocation::find($request->get('location_id')))['braintree_merchant_account_id'];

                $status = Braintree_Transaction::sale([
                    'amount' => $amount_for_braintree,
                    'paymentMethodNonce' => $nonce,
                    'merchantAccountId' => $merchantID,
                    'options' => [
                        'submitForSettlement' => True
                    ]
                ]);
                
                $result = response()->json($status);
                if ($result->original->success) {
                    $order_uid = $request->get('order_uid');
                    $deliverys = DeliveryInCart::where('uid', $order_uid);
                    $deliverys->update(['paid' =>true]);
                    $new_orders = $deliverys->get()->toArray();
                    $this->transactionUtil->increasePointsFromDelivery($new_orders, $order_uid);
                    $resp = DataHelper::make_resp("success", 200, "payment success");
                }
            }

            /* Set Brain Configuration with basic info */
            Braintree_Configuration::environment(env('BRAINTREE_ENV'));
            Braintree_Configuration::merchantId(env('BRAINTREE_MERCHANT_ID'));
            Braintree_Configuration::publicKey(env('BRAINTREE_PUBLIC_KEY'));
            Braintree_Configuration::privateKey(env('BRAINTREE_PRIVATE_KEY'));
			return $resp;
        }
        
		public function getMobileTransactions(Request $request) {
			$business_id = $request->session()->get('user.business_id');
			$invoice_no = $request->get('invoice_no');

			$query = Transaction::leftJoin('transaction_payments', function($join) {
				$join->on('transactions.id', '=', 'transaction_payments.transaction_id');
			})
				->where('transaction_payments.method' ,'mobile')
				->where('business_id', $business_id)
				->where('invoice_no', $invoice_no);

			$transactions = $query->select('transactions.*', 'transaction_payments.card_type as card_type')->get();

			return view('sale_pos.partials.recent_mobile_transactions')
				->with(compact('transactions'));
		}
	}
