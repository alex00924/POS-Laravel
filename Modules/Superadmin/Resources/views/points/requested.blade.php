<!--
 * Created by PhpStorm.
 * User: Piggy
 * Date: 1/8/2019
 * Time: 5:19 PM
 -->
@extends('layouts.app')
@section('title', 'Requested Bullets')

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header no-print">
        <h1>Requested Bullets
            <small></small>
        </h1>
    </section>

    <!-- Main content -->
    <section class="content no-print">

        <div class="box">
            <div class="box-header">
                <h3 class="box-title mt-5">Requested bullets</h3>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="form-group">
                            <div class="input-group">
                                <button type="button" class="btn btn-primary" id="daterange-btn">
                                <span>
                                  <i class="fa fa-calendar"></i> {{ __('messages.filter_by_date') }}
                                </span>
                                    <i class="fa fa-caret-down"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped ajax_view" id="points_table" data-url = '/superadmin/points/requested'>
                        <thead>
                        <tr>
                            <th>Date</th>
                            <th>Business Name</th>
                            <th>Owner</th>
                            <th>Requested Bullets</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>

    </section>

    <!-- approve redeem modal -->
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
    <!-- approve redeem modal end -->
    <!-- /.content -->
@stop
@section('javascript')
    <script src="{{ asset('js/points-superadmin.js') }}"></script>
    <script>
        $(document).ready(function () {
            let points_table_dom = $('#points_table');
            let base_url = points_table_dom.data('url');
            let daterange_btn = $('#daterange-btn');
            //Date range as a button
            daterange_btn.daterangepicker(
                dateRangeSettings,
                function (start, end) {
                    $('#daterange-btn span').html(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
                    points_table.ajax.url( base_url + '?start_date=' + start.format('YYYY-MM-DD') +
                        '&end_date=' + end.format('YYYY-MM-DD') ).load();
                }
            );
            daterange_btn.on('cancel.daterangepicker', function(ev, picker) {
                points_table.ajax.url( base_url).load();
                $('#daterange-btn span').html('<i class="fa fa-calendar"></i> {{ __("messages.filter_by_date") }}');
            });
        });
    </script>
@endsection