<?php
/**
 * Created by PhpStorm.
 * User: Piggy
 * Date: 1/9/2019
 * Time: 9:47 AM
 */
namespace Modules\Superadmin\Http\Controllers;

use App\Business;
use App\Http\DataHelper;
use App\RedeemPoints;
use App\RewardedPoint;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\User;

class PointsController extends BaseController
{
    public function index() {
        $superadmin_business_id = request()->session()->get('user.business_id');
        $total_points = RewardedPoint::where('business_id', '!=', $superadmin_business_id)
            ->where('purchased', true)
            ->sum('points');

        $total_requested_points = RedeemPoints::where('business_id', '!=', $superadmin_business_id)
            ->where('redeem_result', '0')
            ->sum('points');

        $total_approved_points = RedeemPoints::where('business_id', '!=', $superadmin_business_id)
            ->where('redeem_result', '1')
            ->sum('points');

        $total_rejected_points = RedeemPoints::where('business_id', '!=', $superadmin_business_id)
            ->where('redeem_result', '2')
            ->sum('points');

        if (request()->ajax()) {
            $businesses = Business::where('business.id', '!=', $superadmin_business_id)
                ->leftJoin('business_locations as bl', 'bl.business_id', '=', 'business.id')
                ->groupBy('business.id')
                ->select('business.id as business_id', 'business.name as business_name', DB::raw("SUM(bl.points) as remained_points"), 'business.owner_id')
                ->get();
            foreach ($businesses as $business) {
                $user = User::find($business->owner_id);

                $total_points = RewardedPoint::where('business_id', $business->business_id)
                    ->where('purchased', true)
                    ->sum('points');
                $requested_points =  RedeemPoints::where('business_id', $business->business_id)
                    ->where('redeem_result', '0')
                    ->sum('points');
                $approved_points =  RedeemPoints::where('business_id', $business->business_id)
                    ->where('redeem_result', '1')
                    ->sum('points');
                $rejected_points =  RedeemPoints::where('business_id', $business->business_id)
                    ->where('redeem_result', '2')
                    ->sum('points');
                $owner_name = $user->first_name.' '.$user->last_name;
                $business->owner_name = $owner_name;
                $business->total_points = $total_points;
                $business->requested_points = $requested_points;
                $business->approved_points = $approved_points;
                $business->rejected_points = $rejected_points;
            }

            return Datatables::of($businesses)->make(true);
        };

        return view('superadmin::points.index')
            ->with(compact('total_points', 'total_requested_points', 'total_approved_points', 'total_rejected_points'));
    }

    public function requestedPoints() {
        $superadmin_business_id = request()->session()->get('user.business_id');
        if (request()->ajax()) {

            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end =  request()->end_date;

                $requested_points = RedeemPoints::where('business_id', '!=', $superadmin_business_id)
                    ->where('redeem_result', '0')
                    ->whereDate('updated_at', '>=', $start)
                    ->whereDate('updated_at', '<=', $end)
                    ->select('id', 'business_id', 'points', 'updated_at as date')
                    ->get();
            } else {
                $requested_points = RedeemPoints::where('business_id', '!=', $superadmin_business_id)
                    ->where('redeem_result', '0')
                    ->select('id', 'business_id', 'points', 'updated_at as date')
                    ->get();
            }

            foreach ($requested_points as $requested_point) {
                $business =Business::find($requested_point->business_id)
                    ->select('name as business_name', 'owner_id')
                    ->first();
                $user = User::find($business->owner_id);
                $owner_name = $user->first_name.' '.$user->last_name;
                $requested_point->owner_name = $owner_name;
                $requested_point->business_name = $business->business_name;
            }

            return Datatables::of($requested_points)
                ->addColumn('action', function ($row) {
                    $html = '<a href="javascript:void(0)" class="btn btn-sm btn-info approve_redeem"
                                data-id="'.$row->id.'"
                                data-name="'.$row->business_name.'"
                                data-points="'.$row->points.'">
                                Approve
                            </a>';
                    return $html;
                })
                ->setRowAttr([
                    'data-id' => function ($row) {
                        return $row->id;
                    }])
                ->make(true);
        };

        return view('superadmin::points.requested');
    }

    public function approvedPoints() {
        $superadmin_business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {

            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end =  request()->end_date;

                $approved_points = RedeemPoints::where('business_id', '!=', $superadmin_business_id)
                    ->where('redeem_result', '1')
                    ->whereDate('updated_at', '>=', $start)
                    ->whereDate('updated_at', '<=', $end)
                    ->select('id', 'business_id', 'points', 'updated_at as date')
                    ->get();
            } else {
                $approved_points = RedeemPoints::where('business_id', '!=', $superadmin_business_id)
                    ->where('redeem_result', '1')
                    ->select('id', 'business_id', 'points', 'updated_at as date')
                    ->get();
            }

            foreach ($approved_points as $approved_point) {
                $business =Business::find($approved_point->business_id)
                    ->select('name as business_name', 'owner_id')
                    ->first();
                $user = User::find($business->owner_id);
                $owner_name = $user->first_name.' '.$user->last_name;
                $approved_point->owner_name = $owner_name;
                $approved_point->business_name = $business->business_name;
            }

            return Datatables::of($approved_points)->make(true);
        };

        return view('superadmin::points.approved');
    }

    public function rejectedPoints() {
        $superadmin_business_id = request()->session()->get('user.business_id');
        if (request()->ajax()) {

            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end =  request()->end_date;

                $rejected_points = RedeemPoints::where('business_id', '!=', $superadmin_business_id)
                    ->where('redeem_result', '2')
                    ->whereDate('updated_at', '>=', $start)
                    ->whereDate('updated_at', '<=', $end)
                    ->select('id', 'business_id', 'points', 'updated_at as date')
                    ->get();
            } else {
                $rejected_points = RedeemPoints::where('business_id', '!=', $superadmin_business_id)
                    ->where('redeem_result', '2')
                    ->select('id', 'business_id', 'points', 'updated_at as date')
                    ->get();
            }

            foreach ($rejected_points as $rejected_point) {
                $business =Business::find($rejected_point->business_id)
                    ->select('name as business_name', 'owner_id')
                    ->first();
                $user = User::find($business->owner_id);
                $owner_name = $user->first_name.' '.$user->last_name;
                $rejected_point->owner_name = $owner_name;
                $rejected_point->business_name = $business->business_name;
            }

            return Datatables::of($rejected_points)->make(true);
        };

        return view('superadmin::points.rejected');
    }

    public function businessesPoints() {
        $superadmin_business_id = request()->session()->get('user.business_id');
        $businesses = Business::where('business.id', '!=', $superadmin_business_id)
            ->leftJoin('business_locations as bl', 'bl.business_id', '=', 'business.id')
            ->groupBy('business.id')
            ->select('business.name', DB::raw("SUM(bl.points) as points"))
            ->get();
        return $businesses;
    }

    /** Approve requested redeem points
     * @param $request_redeem_id
     * @return array
     */
    public function approveRedeemPoints($request_redeem_id) {

        $result = RedeemPoints::where('id', $request_redeem_id)
            ->update(['redeem_result' => '1']);
        $resp = DataHelper::make_resp('error', 400, 'Something went wrong while processing.');
        if($result > 0) {
            $resp = DataHelper::make_resp('success', 200, 'Successfully approved.');
        }
        return $resp;
    }

    /** Reject requested redeem points
     * @param $request_redeem_id
     * @return array
     */
    public function rejectRedeemPoints($request_redeem_id) {
        $object = RedeemPoints::find($request_redeem_id);
        $points = $object['points'];
        $business_id = $object['business_id'];

        $reject_result = RedeemPoints::where('id', $request_redeem_id)
            ->update(['redeem_result' => '2']);
        $resp = DataHelper::make_resp('error', 400, 'Something went wrong while processing.');
        if ($reject_result) {
            Business::where('id', $business_id)->increment('points', $points);
            $resp = DataHelper::make_resp('success', 200, 'Successfully rejected .');

            $business_owner = Business::find($business_id)->owner;
            $mail_headers = "From: ".env('MAIL_FROM_ADDRESS');
            $subject = 'Redeem request has been rejected.';
            $message = 'Sorry, your request for '.$points.' points redeem has been rejected.';
            DataHelper::send_mail($business_owner->email, $subject, $message, $mail_headers);
            $resp = DataHelper::make_resp('success', 200, 'Successfully rejected.');
        }
        return $resp;
    }
}