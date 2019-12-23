<!--
/**
 * Created by PhpStorm.
 * User: Piggy
 * Date: 1/8/2019
 * Time: 5:52 PM
 */
-->
<!-- Used for request redeem bullets -->
<div class="modal fade" tabindex="-1" role="dialog" id="redeem_points_modal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Redeem</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="form-group">
                            <label for="redeem_points">Bullets to Redeem</label>
                            <label for="redeem_points" style="float: right">
                                <span class="text-danger">*</span>Your Available Bullets:
                                <span class="current-points">{{$total_points->points}}</span>
                                <span class="your-points"></span>
                            </label>
                            <input type="hidden" id="row-id">
                            <input type="number" min="0" max="{{$total_points->points}}" class="form-control" placeholder="Input amount of points to redeem" id="redeem_points_field" autofocus>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-info" id="btn-redeem">
                    <i class="fa fa-send"></i> SEND</button>
            </div>

        </div>
    </div>
</div>
<!-- redeem modal end -->

<!-- approve redeem modal -->
@if(Auth()->user()->can('superadmin'))
    <div class="modal fade" tabindex="-1" role="dialog" id="approve_redeem_modal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Approve Redeem</h4>
                </div>
                <div class="modal-body">

                    <div class="alert alert-info">
                        <span class="business-name modal-business-name"></span>
                        <span class="redeem-message modal-redeem-message">requested
								<span class="redeem-points modal-redeem-points"></span>
								bullets for redeem.
							</span>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-success btn-sm button-redeem" data-method="approve">
                        <i class="fa fa-check"></i> Approve
                    </button>
                    <button type="button" class="btn btn-danger btn-sm button-redeem" data-method="reject">
                        Reject
                    </button>
                    <button type="button" class="btn btn-default btn-sm button-redeem" data-method="cancel">
                        Cancel
                    </button>
                </div>

            </div>
        </div>
    </div>
@endif
<!-- approve redeem modal end -->