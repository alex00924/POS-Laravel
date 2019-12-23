<!--
/**
 * Created by PhpStorm.
 * User: Piggy
 * Date: 1/8/2019
 * Time: 6:23 PM
 */
-->

@extends('layouts.app')
@section('title', 'Approved Bullets')

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header no-print">
        <h1>Approved Bullets
            <small></small>
        </h1>
    </section>

    <!-- Main content -->
    <section class="content no-print">

        <div class="box">
            <div class="box-header">
                <h3 class="box-title mt-5">Approved bullets</h3>
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
                    <table class="table table-bordered table-striped ajax_view" id="points_table" data-url = '/superadmin/points/approved'>
                        <thead>
                        <tr>
                            <th>Date</th>
                            <th>Business Name</th>
                            <th>Owner</th>
                            <th>Approved Bullets</th>
                        </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>

    </section>

    <!-- /.content -->
@stop
@section('javascript')
    <script src="{{ asset('js/points-superadmin.js') }}"></script>
    <script src="{{ asset('js/canvasjs.min.js') }}"></script>
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