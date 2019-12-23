<?php

namespace Modules\Superadmin\Http\Controllers;

use App\Business,
    App\User,
    App\Product,
    App\VariationLocationDetails,
    App\Transaction,
    Spatie\Permission\Models\Permission;

use App\Http\DataHelper;
use App\ProductInCart;
use App\RedeemPoints;
use App\RewardedPoint;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Utils\BusinessUtil;

class BusinessController extends BaseController
{

    protected $businessUtil;

    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(BusinessUtil $businessUtil)
    {
        $this->businessUtil = $businessUtil;
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        if (!auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        $date_today = \Carbon::today();

        $businesses = Business::orderby('name')
                    ->with(['subscriptions' => function($query) use($date_today) {
                        $query->whereDate('start_date', '<=', $date_today)
                            ->whereDate('end_date', '>=', $date_today);
                    }])
                    ->paginate(21);
        
        $business_id = request()->session()->get('user.business_id');
        return view ('superadmin::business.index')
            ->with(compact('businesses', 'business_id'));
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        if (!auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        $currencies = $this->businessUtil->allCurrencies();
        $timezone_list = $this->businessUtil->allTimeZones();

        $accounting_methods = $this->businessUtil->allAccountingMethods();

        $months = array();
        for ($i=1; $i<=12 ; $i++) { 
            $months[$i] = __( 'business.months.' . $i );
        }

        $is_admin = true;

        return view('superadmin::business.create')
            ->with(compact('currencies', 'timezone_list', 'accounting_methods', 
                'months', 'is_admin'));
    }

    /**
     * Store a newly created resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();

            //Create owner.
            $owner_details = $request->only(['surname', 'first_name', 'last_name', 'username', 'email', 'password']);
            $user = User::create_user($owner_details);

            $business_details = $request->only(['name', 'start_date', 'currency_id', 'tax_label_1', 'tax_number_1', 'tax_label_2', 'tax_number_2', 'time_zone', 'accounting_method', 'fy_start_month', 'business_type']);

            $business_location = $request->only(['name', 'country', 'state', 'city', 'zip_code', 'landmark']);
                
            //Create the business
            $business_details['owner_id'] = $user->id;
            if (!empty($business_details['start_date'])) {
                $business_details['start_date'] = \Carbon::createFromFormat('m/d/Y', $business_details['start_date'])->toDateString();
            }
                
            //upload logo
            if ($request->hasFile('business_logo') && $request->file('business_logo')->isValid()) {
                $path = $request->business_logo->store('public/business_logos');
                $business_details['logo'] = str_replace('public/business_logos/', '', $path);
            }
                
            $business = $this->businessUtil->createNewBusiness($business_details);

            //Update user with business id
            $user->business_id = $business->id;
            $user->save();

            $this->businessUtil->newBusinessDefaultResources($business->id, $user->id);
            $new_location = $this->businessUtil->addLocation($business->id, $business_location);

            //create new permission with the new location
            Permission::create(['name' => 'location.' . $new_location->id ]);

            DB::commit();

            $output = array('success' => 1, 
                            'msg' => __('business.business_created_succesfully')
                        );

            return redirect()
                ->action('\Modules\Superadmin\Http\Controllers\BusinessController@index')
                ->with('status', $output);

        } catch(\Exception $e){
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = array('success' => 0, 
                            'msg' => __('messages.something_went_wrong')
                        );

            return back()->with('status', $output)->withInput();
        }
    }

    /**
     * Show the specified resource.
     * @return Response
     */
    public function show($business_id)
    {
        if (!auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        $business = Business::with(['currency', 'locations', 'subscriptions'])->find($business_id);
        
        $owner_id = $business->owner_id;
        $created_id = $business->created_by;

        $user = User::find($owner_id);
        $created_by = '';
        if(!empty($created_id))
        {
            $created_by = User::find($created_id);
        }
        return view('superadmin::business.show')
            ->with(compact('business', 'user', 'created_by'));
    }

    /**
     * Show the form for editing the specified resource.
     * @return Response
     */
    public function edit()
    {
        return view('superadmin::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function update(Request $request)
    {
    }

    /**
     * Remove the specified resource from storage.
     * @return Response
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            
            $notAllowed = $this->businessUtil->nowAllowedInDemo();
            if(!empty($notAllowed)){
                return $notAllowed;
            }

            //Check if logged in busines id is same as deleted business then not allowed.
            $business_id = request()->session()->get('user.business_id');
            if($business_id == $id){
                $output = ['success' => 0, 'msg' => __('superadmin.lang.cannot_delete_current_business')];
                return back()->with('status', $output);
            }

            DB::beginTransaction();

            //Delete related products & transactions.
            $products_id = Product::where('business_id', $id)->pluck('id')->toArray();
            if(!empty($products_id)){
                VariationLocationDetails::whereIn('product_id', $products_id)->delete();
                /** 2018-12-25 caixia*/
                /** delete related products sold from mobile */
                ProductInCart::whereIn('product_id', $products_id)->delete();
            }
            Transaction::where('business_id', $id)->delete();
            /** 2018-12-25 caixia*/
            /** delete related rewarded points sold from mobile */
            RewardedPoint::where('business_id', $id)->delete();

            Business::where('id', $id)
                ->delete();

            DB::commit();

            $output = ['success' => 1, 'msg' => __('lang_v1.success')];
            return redirect()
                ->action('\Modules\Superadmin\Http\Controllers\BusinessController@index')
                ->with('status', $output);
        
        } catch(\Exception $e){
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

            $output = array('success' => 0, 
                            'msg' => __('messages.something_went_wrong')
                        );

            return back()->with('status', $output)->withInput();
        }
    }

    /**
     * Changes the activation status of a business.
     * @return Response
     */
    public function toggleActive(Request $request, $business_id, $is_active){
        
        if (!auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        $notAllowed = $this->businessUtil->nowAllowedInDemo();
        if(!empty($notAllowed)){
            return $notAllowed;
        }
            
        Business::where('id', $business_id)
            ->update(['is_active' => $is_active]);

        $output = ['success' => 1, 
                    'msg' => __('lang_v1.success')
                ];
        return back()->with('status', $output);
    }

}
