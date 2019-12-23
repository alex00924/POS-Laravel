<?php

namespace App\Http\Controllers;

use App\Business;
use App\Http\DataHelper;
use App\RedeemPoints;
use App\RewardedPoint;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use App\BusinessLocation;


class PointsController extends Controller
{
    public function index() {
        if (!auth()->user()) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $locations = BusinessLocation::forDropdown($business_id, true);
        if( is_null($locations) )
            abort(403, 'Unauthorized action.');

        $location = request()->business_location;

        if( is_null($location) && count($locations) < 2 )
            $location = array_keys($locations->toArray())[0];

        $business_points = new \stdClass;
        $requested_points = new \stdClass;
        $approved_points = new \stdClass;
        $rejected_points = new \stdClass;
    
        $business_points->points = 0;
        $requested_points->points  = 0;
        $approved_points->points  = 0;
        $rejected_points->points  = 0;
    
        if( is_null($location) ){
            $business_points->points = BusinessLocation::where('business_id', $business_id)->sum('points');
        }
        else{
            $business_detail = BusinessLocation::where('id', $location)->select('points', 'name')->first();
            $business_points->points = $business_detail->points;
        }
    
        if (request()->ajax()) {

            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end =  request()->end_date;
                if( is_null($location) && count($locations) > 1 )
                    $points = RewardedPoint::where('business_id', $business_id);
                else
                    $points = RewardedPoint::where('location_id', $location);
                
                $points = $points->where('purchased', true)
                    ->whereDate('created_at', '>=', $start)
                    ->whereDate('created_at', '<=', $end)
                    ->get();
            } else {
                if( is_null($location) && count($locations) > 2 )
                    $points = RewardedPoint::where('business_id', $business_id);
                else
                    $points = RewardedPoint::where('location_id', $location);

                $points = $points->where('purchased', true)->get();
            }
            foreach ($points as $point) {
                if(str_contains($point->cart_uid, 'ccard-')){
                    $point->used_with = 'Credit Card';
                } else {
                    $point->used_with = 'Cash pay';
                }
            }
            return Datatables::of($points)
                ->addColumn('action', function ($row) {
                    $total_row_price = $row->point_ratio * $row->points;
                    return '<input type="hidden" class="total-point-price" value="'.$total_row_price.'" data-orig-value="'.$total_row_price.'">'.
                           '<input type="hidden" class="row-points" value="'.$row->points.'" data-orig-value="'.$row->points.'">';
                })
                ->setRowAttr([
                    'data-id' => function ($row) {
                        return $row->id;
                    }])
                ->removeColumn('id')
                ->make(true);
        };

        return view('points.index', ['total_points' => $business_points, 'locations' => $locations, 'location' => $location]);
    }

    public function businessPoints() {
        $business_id = request()->session()->get('user.business_id');
        $business_location = request()->business_location;
        
        if( is_null($business_location) ){
            $business_name = Business::where('id', $business_id)->select('name')->first()->name;
            $business_points = BusinessLocation::where('business_id', $business_id)->sum('points');
        }
        else{
            $business_detail = BusinessLocation::where('id', $business_location)->select('points', 'name')->first();
            $business_name = $business_detail->name;
            $business_points = $business_detail->points;
        }
        $requested_points = RedeemPoints::where('business_id', $business_id)
            ->where('redeem_result', '0');
        $approved_points =  RedeemPoints::where('business_id', $business_id)
            ->where('redeem_result', '1');

        $rejected_points =  RedeemPoints::where('business_id', $business_id)
            ->where('redeem_result', '2');

        if( !is_null($business_location) )
        {
            $requested_points = $requested_points->where('business_loc_id', $business_location);    
            $approved_points  = $approved_points->where('business_loc_id', $business_location);    
            $rejected_points  = $rejected_points->where('business_loc_id', $business_location);    
        }
        $requested_points = $requested_points->sum('points');
        $approved_points  = $approved_points->sum('points');
        $rejected_points  = $rejected_points->sum('points');
        return compact('business_points', 'requested_points', 'approved_points', 'requested_points', 'business_name', 'rejected_points');
    }

    public function requestedPoints(){
        if (!auth()->user()) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');

        $locations = BusinessLocation::forDropdown($business_id, true);
        if( is_null($locations) )
            abort(403, 'Unauthorized action.');

        $location = request()->business_location;

        if( is_null($location) && count($locations) < 2 )
            $location = array_keys($locations->toArray())[0];

        $business_points = new \stdClass;
        $requested_points = new \stdClass;
        $approved_points = new \stdClass;
        $rejected_points = new \stdClass;
    
        $business_points->points = 0;
        $requested_points->points  = 0;
        $approved_points->points  = 0;
        $rejected_points->points  = 0;

        if (request()->ajax()) {

            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end =  request()->end_date;
                if( is_null($location) && count($locations) > 1 )
                    $points = RedeemPoints::where('business_id', $business_id);
                else
                    $points = RedeemPoints::where('business_loc_id', $location);
                
                $points = $points->where('redeem_result', '0')
                    ->whereDate('created_at', '>=', $start)
                    ->whereDate('created_at', '<=', $end)
                    ->get();
            } else {
                if( is_null($location) && count($locations) > 1 )
                    $points = RedeemPoints::where('business_id', $business_id);
                else
                    $points = RedeemPoints::where('business_loc_id', $location);
                $points = $points->where('redeem_result', '0')->get();
            }
            
            return Datatables::of($points)
                ->addColumn('action', function ($row) {
                    return '<input type="hidden" class="row-points" value="'.$row->points.'" data-orig-value="'.$row->points.'">';
                })
                ->setRowAttr([
                    'data-id' => function ($row) {
                        return $row->id;
                    }])
                ->removeColumn('id')
                ->make(true);
        };

        return view('points.requested', ['total_points' => $business_points, 'requested_points' => $requested_points, 'approved_points' => $approved_points, 'rejected_points' => $rejected_points, 'locations' => $locations, 'location' => $location]);

    }

    public function approvedPoints() {
        if (!auth()->user()) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        
        $locations = BusinessLocation::forDropdown($business_id, true);
        if( is_null($locations) )
            abort(403, 'Unauthorized action.');

        $location = request()->business_location;

        if( is_null($location) && count($locations) < 2 )
            $location = array_keys($locations->toArray())[0];

        $business_points = new \stdClass;
        $requested_points = new \stdClass;
        $approved_points = new \stdClass;
        $rejected_points = new \stdClass;
    
        $business_points->points = 0;
        $requested_points->points  = 0;
        $approved_points->points  = 0;
        $rejected_points->points  = 0;

        if (request()->ajax()) {
            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end =  request()->end_date;
                if( is_null($location) && count($locations) > 1 )
                    $points = RedeemPoints::where('business_id', $business_id);
                else
                    $points = RedeemPoints::where('business_loc_id', $location);
                
                $points = $points->where('redeem_result', '1')
                    ->whereDate('created_at', '>=', $start)
                    ->whereDate('created_at', '<=', $end)
                    ->get();
            } else {
                if( is_null($location) && count($locations) > 1 )
                    $points = RedeemPoints::where('business_id', $business_id);
                else
                    $points = RedeemPoints::where('business_loc_id', $location);
                $points = $points->where('redeem_result', '1')->get();
            }
            
            return Datatables::of($points)
                ->addColumn('action', function ($row) {
                    return '<input type="hidden" class="row-points" value="'.$row->points.'" data-orig-value="'.$row->points.'">';
                })
                ->setRowAttr([
                    'data-id' => function ($row) {
                        return $row->id;
                    }])
                ->removeColumn('id')
                ->make(true);
        };

        return view('points.approved', ['total_points' => $business_points, 'requested_points' => $requested_points, 'approved_points' => $approved_points, 'rejected_points' => $rejected_points, 'locations' => $locations, 'location' => $location]);

    }

    public function rejectedPoints() {
        if (!auth()->user()) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        
        $locations = BusinessLocation::forDropdown($business_id, true);
        if( is_null($locations) )
            abort(403, 'Unauthorized action.');

        $location = request()->business_location;

        if( is_null($location) && count($locations) < 2 )
            $location = array_keys($locations->toArray())[0];

        $business_points = new \stdClass;
        $requested_points = new \stdClass;
        $approved_points = new \stdClass;
        $rejected_points = new \stdClass;
    
        $business_points->points = 0;
        $requested_points->points  = 0;
        $approved_points->points  = 0;
        $rejected_points->points  = 0;

        if (request()->ajax()) {
            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end =  request()->end_date;
                if( is_null($location) && count($locations) > 1 )
                    $points = RedeemPoints::where('business_id', $business_id);
                else
                    $points = RedeemPoints::where('business_loc_id', $location);
                
                $points = $points->where('redeem_result', '2')
                    ->whereDate('created_at', '>=', $start)
                    ->whereDate('created_at', '<=', $end)
                    ->get();
            } else {
                if( is_null($location) && count($locations) > 1 )
                    $points = RedeemPoints::where('business_id', $business_id);
                else
                    $points = RedeemPoints::where('business_loc_id', $location);
                $points = $points->where('redeem_result', '2')->get();
            }
            
            return Datatables::of($points)
                ->addColumn('action', function ($row) {
                    return '<input type="hidden" class="row-points" value="'.$row->points.'" data-orig-value="'.$row->points.'">';
                })
                ->setRowAttr([
                    'data-id' => function ($row) {
                        return $row->id;
                    }])
                ->removeColumn('id')
                ->make(true);
        };

        return view('points.rejected', ['total_points' => $business_points, 'requested_points' => $requested_points, 'approved_points' => $approved_points, 'rejected_points' => $rejected_points, 'locations' => $locations, 'location' => $location]);

    }

    /** Request redeem points
     * @param $points
     * @return array
     */
    public function requestRedeemPoints($points) {
        if(request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $user_email = request()->session()->get('user.email');
            $business_loc_id = request()->business_location;
            if( is_null($business_loc_id) )
                $business_name = Business::find($business_id)->name;
            else
                $business_name = BusinessLocation::find($business_loc_id)->name;

            $resp = DataHelper::make_resp('error', 400, 'Something went wrong while processing.');

            $admin = User::where('username', env('ADMINISTRATOR_USERNAMES'))->first();

            $mail_headers = "From: ".$user_email;
            $mail_headers .= "Business: ".$business_name;
            $subject = 'Requested Points Redeem';
            $message = $business_name.' has requested '.$points.' for redeem.';
            DataHelper::send_mail($admin->email, $subject, $message, $mail_headers);
            RedeemPoints::create(compact('business_id', 'points', 'business_loc_id'));
            BusinessLocation::where('id', $business_loc_id)->decrement('points', $points);
            $resp = DataHelper::make_resp('success', 200, $points.'points has been successfully requested.');
            

            return $resp;
        }
    }
}
