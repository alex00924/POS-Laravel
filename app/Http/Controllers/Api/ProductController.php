<?php

	namespace App\Http\Controllers\Api;

	use App\Http\DataHelper;
	use App\Product;
	use App\ProductInCart;

    use App\RewardedPoint;
    use App\Variation;
    use App\VariationLocationDetails;
    use Illuminate\Http\Request;
	use App\Http\Controllers\Controller;
    use App\VariationGroupPrice;
    use App\TaxRate;
    use App\Delivery;
    use App\Restaurant\ResTable;
    use App\DeliveryInCart;
    
    class ProductController extends Controller
	{
		protected $dataHelper;

		public function __construct(DataHelper $dataHelper)
		{
			$this->dataHelper = $dataHelper;
		}

        //get default selling group description of a product
        public function getSellingGroup($variation_id, $group_name, $tax_id)
        {
            $variation_group = VariationGroupPrice::join('selling_price_groups', 'variation_group_prices.price_group_id', '=', 'selling_price_groups.id')
                    ->where('variation_group_prices.variation_id', $variation_id)
                    ->where('selling_price_groups.name', $group_name)
                    ->select('selling_price_groups.id', 'price_inc_tax')
                    ->first();
            $resp = array();
            $resp['state'] = -1;    //Promo Not Valid for this Product
            $resp['group_price'] = 0;
            $resp['group_id'] = 0;
            if( empty($variation_group) )
                return $resp;

            $price_inc_tax = $variation_group->price_inc_tax;

            
            if(!empty($price_inc_tax) && !empty($tax_id)) {
                $resp['state'] = 1;
                $tax_amount = TaxRate::where('id', $tax_id)->value('amount');
                $resp['group_price'] = ($price_inc_tax * 100) / (100 + $tax_amount);
                $resp['group_id'] = $variation_group->id;
            }
            return $resp;
        }

        
        /**
         * get product's info from barcode(sku)
         * @param $business_id
         * @param $location_id
         * @return array
         */
		public function getAllProduct($business_id, $location_id)
		{
            $products = Product::where('business_id', $business_id)
                               ->select('id', 'name', 'sku', 'image', 'business_id', 'enable_stock', 'tax_type', 'tax')
                                ->get();
            $product_arry = array();
            
            if(empty($products))
            {
                $resp = $this->dataHelper->make_resp("success", 200, "successfully get info");
                $resp['products'] = $product_arry;
                return $resp;
            }

            foreach($products as $product)
            {
                if($product->enable_stock == 0) 
                    continue;
                
                $variations = Variation::where('product_id', $product->id)
                                ->select('product_variation_id', 'id', 'default_sell_price', 'sell_price_inc_tax', 'default_purchase_price', 'dpp_inc_tax', 'profit_percent')
                                ->get();

                foreach($variations as $variation) {
                    $current_product = clone $product;
                    $available_qty = VariationLocationDetails::where('product_variation_id', $variation->product_variation_id)
                                        ->where('location_id', $location_id)
                                        ->select('qty_available')->get();
    
                    if($this->dataHelper->validate_data($available_qty) && ($available_qty[0]->qty_available > 0))
                    {
                        if( empty($variation) )
                            continue;

                        //Real price = sell_price_inc_tax = dpp_inc_tax * (100 + profit_percent) / 100
                        $current_product->price = $variation->sell_price_inc_tax;
                        $current_product->default_purchase_price = $variation->default_purchase_price;
                        $current_product->dpp_inc_tax = $variation->dpp_inc_tax;
                        $current_product->profit_percent = $variation->profit_percent;
                        $current_product->default_sell_price = $variation->default_sell_price;
                        $current_product->variation_id = $variation->id;
                        $current_product->sell_price_inc_tax = $variation->sell_price_inc_tax;
                        $current_product->qty_available = $available_qty[0]->qty_available;
                        
                        $current_product->variation_group = VariationGroupPrice::join('selling_price_groups', 'variation_group_prices.price_group_id', '=', 'selling_price_groups.id')
                            ->where('variation_group_prices.variation_id', $variation->id)
                            ->where('selling_price_groups.location_id', $location_id)
                            ->select('selling_price_groups.id', 'selling_price_groups.name', 'price_inc_tax')
                            ->get()->toArray();

                        $product_arry[] = $current_product;
                    }
                }
            } 
            $resp = $this->dataHelper->make_resp("success", 200, "successfully get info");
            $resp['products'] = $product_arry;
            return $resp;
		}

        /**
         * get product's info from barcode(sku)
         * @param $business_id
         * @param $location_id
         * @return array
         */
		public function getDeliveryProduct($business_id, $location_id)
		{
            $products = Product::where('business_id', $business_id);
				
            $delivery = Delivery::where('business_loc_id', $location_id)->first();

            if( !$delivery->is_all )
            {
                $products = $products->whereIn('id', json_decode($delivery->product_ids));
            }

            $products = $products->select('id', 'name', 'sku', 'image', 'business_id', 'enable_stock', 'tax_type', 'tax')
                                ->get();
            $product_arry = array();
            
            if(empty($products))
            {
                $resp = $this->dataHelper->make_resp("success", 200, "successfully get info");
                $resp['products'] = $product_arry;
                return $resp;
            }

            foreach($products as $product)
            {
                if($product->enable_stock == 0) 
                    continue;
                
                $variations = Variation::where('product_id', $product->id)
                                ->select('product_variation_id', 'id', 'default_sell_price', 'sell_price_inc_tax', 'default_purchase_price', 'dpp_inc_tax', 'profit_percent')
                                ->get();

                foreach($variations as $variation) {
                    $current_product = clone $product;
                    $available_qty = VariationLocationDetails::where('product_variation_id', $variation->product_variation_id)
                                        ->where('location_id', $location_id)
                                        ->select('qty_available')->get();
    
                    if($this->dataHelper->validate_data($available_qty) && ($available_qty[0]->qty_available > 0))
                    {
                        if( empty($variation) )
                            continue;

                        //Real price = sell_price_inc_tax = dpp_inc_tax * (100 + profit_percent) / 100
                        $current_product->price = $variation->sell_price_inc_tax;
                        $current_product->default_purchase_price = $variation->default_purchase_price;
                        $current_product->dpp_inc_tax = $variation->dpp_inc_tax;
                        $current_product->profit_percent = $variation->profit_percent;
                        $current_product->default_sell_price = $variation->default_sell_price;
                        $current_product->variation_id = $variation->id;
                        $current_product->sell_price_inc_tax = $variation->sell_price_inc_tax;
                        $current_product->qty_available = $available_qty[0]->qty_available;
                        
                        $current_product->variation_group = VariationGroupPrice::join('selling_price_groups', 'variation_group_prices.price_group_id', '=', 'selling_price_groups.id')
                            ->where('variation_group_prices.variation_id', $variation->id)
                            ->where('selling_price_groups.location_id', $location_id)
                            ->select('selling_price_groups.id', 'selling_price_groups.name', 'price_inc_tax')
                            ->get()->toArray();

                        $product_arry[] = $current_product;
                    }
                }
            } 
            $resp = $this->dataHelper->make_resp("success", 200, "successfully get info");
            $resp['products'] = $product_arry;
            return $resp;
		}

        /**
         * get product's info from barcode(sku)
         * @param $sku
         * @param $business_id
         * @param $location_id
         * @return array
         */
		public function getProduct($sku, $business_id, $location_id)
		{
			$products = Product::where('sku', $sku)
				->where('business_id', $business_id)
				->select('id', 'name', 'sku', 'image', 'business_id', 'enable_stock', 'tax_type', 'tax')
				->get();

            foreach($products as $product)
            {
                if($product->enable_stock != 0) 
                {
                    $variation_id = Variation::where('product_id', $product->id)->select('product_variation_id', 'id')->get();
                    $available_qty = VariationLocationDetails::where('product_variation_id', $variation_id[0]->product_variation_id)
                        ->where('location_id', $location_id)
                        ->select('qty_available')->get();
        
                    if($this->dataHelper->validate_data($available_qty) && ($available_qty[0]->qty_available > 0))
                    {
                        $variation = Variation::where('product_id', $product->id)
                            ->select('id', 'default_sell_price', 'sell_price_inc_tax', 'default_purchase_price', 'dpp_inc_tax', 'profit_percent')
                            ->get();

                        //Real price = sell_price_inc_tax = dpp_inc_tax * (100 + profit_percent) / 100
                        $product->price = $variation[0]->sell_price_inc_tax;
                        $product->default_purchase_price = $variation[0]->default_purchase_price;
                        $product->dpp_inc_tax = $variation[0]->dpp_inc_tax;
                        $product->profit_percent = $variation[0]->profit_percent;
                        $product->default_sell_price = $variation[0]->default_sell_price;
                        $product->variation_id = $variation[0]->id;
                        $product->sell_price_inc_tax = $variation[0]->sell_price_inc_tax;
                        $product->qty_available = $available_qty[0]->qty_available;

                        $product->variation_group = VariationGroupPrice::join('selling_price_groups', 'variation_group_prices.price_group_id', '=', 'selling_price_groups.id')
                                                    ->where('variation_group_prices.variation_id', $variation_id[0]->id)
                                                    ->where('selling_price_groups.location_id', $location_id)
                                                    ->select('selling_price_groups.id', 'selling_price_groups.name', 'price_inc_tax')
                                                    ->get()->toArray();
                                                    
                        $resp = $this->dataHelper->make_resp("success", 200, "successfully get info");
                        $resp['productInfo'] = $product;
                        return $resp;
                        exit;
                    }
                }
                else{
                    $resp = $this->dataHelper->make_resp("fail", 400, "Product out of stock");
                    $resp['productInfo'] = null;
                    return $resp;
                }

            } 

                
            $product = array();
            //////Nothing
            $variation = Variation::where('sub_sku', "$sku")
                ->join('variation_location_details as loc',
                    'variations.id',
                    '=',
                    'loc.variation_id'
                )
                ->join('products',
                    'products.id',
                    '=',
                    'variations.product_id'
                )
                ->where('loc.location_id', $location_id)
                ->select('variations.id', 'variations.name as v_name', 'default_sell_price', 'sell_price_inc_tax', 'default_purchase_price', 'dpp_inc_tax', 'profit_percent', 'loc.product_id', 'products.image', 'products.enable_stock', 'products.tax_type', 'products.tax', 'products.name')
                ->get();

            if(count($variation) > 0)
            {
                $available_qty = VariationLocationDetails::where('variation_id', $variation[0]->id)
                        ->where('location_id', $location_id)
                        ->select('qty_available')->get();
                if($this->dataHelper->validate_data($available_qty) && ($available_qty[0]->qty_available > 0))
                {
                    $product['sku'] = $sku;
                    $product['name'] = $variation[0]->name . "  " . $variation[0]->v_name;
                    $product['image'] = $variation[0]->image;
                    $product['business_id'] = $business_id;
                    $product['enable_stock'] = $variation[0]->enable_stock;
                    $product['tax_type'] = $variation[0]->tax_type;
                    $product['tax'] = $variation[0]->tax;
                    
                    $product['id']=$variation[0]->product_id;
                    $product['price'] = $variation[0]->default_sell_price;
                    $product['default_purchase_price'] = $variation[0]->default_purchase_price;
                    $product['dpp_inc_tax'] = $variation[0]->dpp_inc_tax;
                    $product['profit_percent'] = $variation[0]->profit_percent;
                    $product['default_sell_price'] = $variation[0]->default_sell_price;
                    $product['variation_id'] = $variation[0]->id;
                    $product['sell_price_inc_tax'] = $variation[0]->sell_price_inc_tax;

                    $product['qty_available'] = $available_qty[0]->qty_available;

                    $resp = $this->dataHelper->make_resp("success", 200, "successfully get info");
                    $resp['productInfo'] = $product;
                    return $resp;
                }
            }

            $resp = $this->dataHelper->make_resp("fail", 400, "No product with this barcode");
            $resp['productInfo'] = null;
            return $resp;
		}

		public function registerProducts(Request $request)
		{
		    $business_id = $request->get('business_id');
		    $location_id = $request->get('location_id');
			$old_uid = $request->get('old_uid');
			$total_price = $request->get('total_cash_price');
			$points = $request->get('points');
			$point_ratio = $request->get('point_ratio');
            $products = $request->get('products');
            $delivery_uid = null;
            if($request->get('is_delivery'))
                $delivery_uid = $request->get('delivery_uid');

            $res_table_id = null;
            if($request->get('res_table_id'))
                $res_table_id = $request->get('res_table_id');
    
			if ($old_uid !== "") {
				ProductInCart::where('uid', $old_uid)->delete();
				RewardedPoint::where('cart_uid', $old_uid)->delete();
			}

			$uid = $this->dataHelper->get_uid();

			foreach($products as $product) {
                $product_id = $product['product_id'];
                $product_quantity = $product['product_quantity'];
                $variation_id = $product['product_variation_id'];
                $selling_group_id = $product['selling_group_id'];

                ProductInCart::create(
                    compact('product_id',
                        'product_quantity',
                        'location_id',
                        'variation_id',
                        'uid',
                        'points',
                        'point_ratio',
                        'total_price',
                        'selling_group_id',
                        'delivery_uid',
                        'res_table_id')
                );
            }

            $cart_uid = $uid;
			RewardedPoint::create(compact('business_id', 'location_id', 'points', 'point_ratio', 'total_price', 'cart_uid'));

			return $this->dataHelper->make_resp('success', 200, $uid);
		}

		public function removeProducts($uid, $mode)
		{
			$products_checked = ProductInCart::where('uid', $uid)->where('checked', 'true')->get();
			$products_unchecked = ProductInCart::where('uid', $uid)->where('checked', 'false')->get();
			$message = "";
			// products are awaiting for check out so dialog in mobile don't dismiss
			if ($this->dataHelper->validate_data($products_checked)) {
				$message = "checked";
			}
			// Products did't get by cashier so after 30secs, dialog will dismiss and products will be unregistered
			if ($this->dataHelper->validate_data($products_unchecked)) {
				ProductInCart::where('uid', $uid)->where('checked', 'false')->delete();
                RewardedPoint::where('cart_uid', $uid)->delete();
				$message = "unchecked";
			}

			// Delete products according to the case
			if ($mode == "delete") {
			    // if products are awaiting check out, can't delete
			    if ($this->dataHelper->validate_data($products_checked)) {
                    $message = "awaiting checkout";
                } else {
                    ProductInCart::where('uid', $uid)->delete();
                    $message = "unchecked";
                }
			}
			return $this->dataHelper->make_resp('success', 200, $message);
		}

		public function cancel_products($uid) {
            ProductInCart::where('uid', $uid)->delete();
            $this->dataHelper->cancel_products_pusher($uid);
            $resp = $this->dataHelper->make_resp('success', 200, 'Products have been canceled successfully.');
            return $resp;
        }

        public function ping_to_app($uid) {
            $this->dataHelper->ping_to_app($uid);
            $resp = $this->dataHelper->make_resp('success', 200, 'Ping successfully.');
            return $resp;
        }

        public function pong_from_app($event) {
            $this->dataHelper->pong_from_app($event);
            $resp = $this->dataHelper->make_resp('success', 200, 'Ping successfully.');
            return $resp;
        }

        public function getTables($business_id, $location_id)
        {
            $tables = ResTable::where('business_id', $business_id)
                            ->where('location_id', $location_id)
                            ->select('name', 'id')
                            ->get();
            $resp = $this->dataHelper->make_resp("success", 200, "successfully get info");
            $resp['tables'] = $tables;
            return $resp;
        }
	}
