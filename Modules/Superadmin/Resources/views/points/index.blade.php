<!--
/**
 * Created by PhpStorm.
 * User: Piggy
 * Date: 1/9/2019
 * Time: 9:51 AM
 */
 -->
@extends('layouts.app')
@section('title', 'Bullets of Businesses')

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header no-print">
        <h1>Bullets of Businesses
            <small></small>
        </h1>
    </section>

    <!-- Main content -->
    <section class="content no-print">

        <div class="box">
            <div class="box-header">
                <div id="chartContainer" style="width: 600px;height: 300px;">
                    <canvas id="pie-chart" class="vertical-align-middle">
                    </canvas>
                </div>
                {{--@can('superadmin')--}}
                {{--<div class="box-tools">--}}
                {{--<a href="javascript:void(0)" class="btn btn-block btn-primary approve_redeem"--}}
                {{--data-id="{{$redeem->id}}"--}}
                {{--data-name="{{$redeem->business->name}}"--}}
                {{--data-points="{{$redeem->points}}">--}}
                {{--<span class="business-name">{{$redeem->business->name}}</span>--}}
                {{--<span class="redeem-message">requested--}}
                {{--<span class="redeem-points">{{$redeem->points}}</span>--}}
                {{--points for redeem.--}}
                {{--</span>--}}
                {{--</a>--}}
                {{--</div>--}}
                {{--@endcan--}}
            </div>
            <div class="box-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped ajax_view" id="points_table" data-url = '/superadmin/points'>
                        <thead>
                        <tr>
                            <th>Business Name</th>
                            <th>Owner</th>
                            <th>Total Bullets</th>
                            <th>Remained Bullets</th>
                            <th>Requested Bullets</th>
                            <th>Approved Bullets</th>
                            <th>Rejected Bullets</th>
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
    <script src="{{ asset('js/chart.min.js') }}"></script>
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