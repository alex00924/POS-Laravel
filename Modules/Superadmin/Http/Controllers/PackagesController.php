<?php

namespace Modules\Superadmin\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Modules\Superadmin\Entities\Package;

use App\Utils\BusinessUtil,
    App\System;
use App\Business;

class PackagesController extends BaseController
{
    /**
     * All Utils instance.
     *
     */
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

        $packages = Package::orderby('sort_order', 'asc')
                    ->paginate(20);

        return view('superadmin::packages.index')
            ->with(compact('packages'));
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

        $intervals = ['days' => 'Days', 'months' => 'Months', 'years' => 'Years'];
        $currency = System::getCurrency();

        return view('superadmin::packages.create')
            ->with(compact('intervals', 'currency'));
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

        try{
            $input = $request->only(['name', 'description', 'location_count', 'user_count', 'product_count', 'invoice_count', 'interval', 'interval_count', 'trial_days', 'price', 'sort_order', 'is_active', 'is_delivery']);

            $currency = System::getCurrency();

            $input['price'] = $this->businessUtil->num_uf($input['price'], $currency);
            $input['is_active'] = empty($input['is_active']) ? 0 : 1;
            $input['is_delivery'] = empty($input['is_delivery']) ? 0 : 1;
            $input['created_by'] = $request->session()->get('user.id');

            $package = new Package;
            $package->fill($input);
            $package->save();

            $output = ['success' => 1, 'msg' => __('lang_v1.success')];

        } catch(\Exception $e){
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
            $output = array('success' => 0, 
                            'msg' => __('messages.something_went_wrong')
                        );
        }

        return redirect()
            ->action('\Modules\Superadmin\Http\Controllers\PackagesController@index')
            ->with('status', $output);
    }

    /**
     * Show the specified resource.
     * @return Response
     */
    public function show()
    {
        return view('superadmin::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @return Response
     */
    public function edit($id)
    {
        $packages = Package::where( 'id', $id)
                            ->first();
        $intervals = ['days' => 'Days', 'months' => 'Months', 'years' => 'Years'];
        return view('superadmin::packages.edit')
               ->with(compact('packages', 'intervals'));
    }

    /**
     * Update the specified resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }

        try{
            $packages_details = $request->only(['name', 'id', 'description', 'location_count', 'user_count', 'product_count', 'invoice_count', 'interval', 'interval_count', 'trial_days', 'price', 'sort_order', 'is_active', 'is_delivery']);
            
            $packages_details['is_active'] = empty($packages_details['is_active']) ? 0 : 1;
            $packages_details['is_delivery'] = empty($packages_details['is_delivery']) ? 0 : 1;
            $package = Package::where('id', $id)
                            ->first();
            $package->fill($packages_details);
            $package->save();   

            if( $packages_details['is_delivery'] == 0 )
            {
                $this->businessUtil->disableDelivery($id);
            }

            $output = ['success' => 1, 'msg' => __('lang_v1.success')];

        } catch(\Exception $e){
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
            $output = array('success' => 0, 
                            'msg' => __('messages.something_went_wrong')
                        );
        }

        return redirect()
            ->action('\Modules\Superadmin\Http\Controllers\PackagesController@index')
            ->with('status', $output);
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

        try{
            Package::where('id', $id)
                ->delete();
            
            $output = ['success' => 1, 'msg' => __('lang_v1.success')];

        } catch(\Exception $e){
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            
            $output = array('success' => 0, 
                            'msg' => __('messages.something_went_wrong')
                        );
        }
    
        return redirect()
            ->action('\Modules\Superadmin\Http\Controllers\PackagesController@index')
            ->with('status', $output);
    }

}
