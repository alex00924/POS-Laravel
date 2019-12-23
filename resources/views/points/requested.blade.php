<!--
 * Created by PhpStorm.
 * User: Piggy
 * Date: 1/8/2019
 * Time: 5:19 PM
 -->
@extends('layouts.app')
@section('title', 'Requested Bullets')
@section('css')
    <style>
        @import 'https://fonts.googleapis.com/css?family=Open+Sans';
        html,
        body {
            width: 100%;
            height: 100%;
        }

        #single_chart {
            width: 100%;
            height: 100%;
            min-height: 250px;
        }

    </style>
@stop
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
                {!! Form::open(['url' => action('PointsController@requestedPoints'), 'method' => 'get', 'id' => 'bussiness_loc_form']) !!}
                    <div class="row">
                        <div class="col-xs-12" style='margin-bottom: 10px'>
                            {!! Form::label('business_location', __('business.business_locations') . ':' ) !!}
                            {!! Form::select('business_location', $locations, $location, ['class' => 'form-control', 'style' => 'width: 100%;' ]); !!}
                        </div>
                    </div>
                {!! Form::close() !!}
                <div id="chartContainer" style="height: 370px; max-width: 920px; margin: 0 auto;">
                    <i class="fa fa-spin fa-spinner fa-2x vertical-align-middle text-muted"></i>
                </div>
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
                    <table class="table table-bordered table-striped ajax_view" id="points_table" data-url = '/points/requested'>
                        <thead>
                        <tr>
                            <th>Date</th>
                            <th>Bullets</th>
                            <th></th>
                            {{--<th>@lang('messages.action')</th>--}}
                        </tr>
                        </thead>
                        <tfoot>
                        <tr class="bg-gray font-17 text-center footer-total">
                            <td><strong>Total bullets:</strong></td>
                            <td><span id="footer_points_total" data-currency_symbol ="false"></span></td>
                            <td>
                                <a onclick='openDlg()' data-toggle="modal" class="btn btn-success btn-xs">
                                    <i class="fa fa-send"></i> Redeem
                                </a>
                            </td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        @include('points.partial.redeem_modal')

    </section>

    <!-- /.content -->
@stop
@section('javascript')
    <script src="{{ asset('js/points.js') }}"></script>
    <script src="{{ asset('js/canvasjs.min.js') }}"></script>
    <script>
        function openDlg()
        {
            if( $('#business_location').val() === '' )
            {
                alert('Please select location');
                return;
            }
            $('#redeem_points_modal').modal('show');
        }
        $(document).ready(function () {
            if( $('#business_location').children().length < 2 )
            {
                $('#bussiness_loc_form').hide();
            }
            let points_table_dom = $('#points_table');
            let base_url = points_table_dom.data('url');
            let daterange_btn = $('#daterange-btn');
            //Date range as a button
            daterange_btn.daterangepicker(
                dateRangeSettings,
                function (start, end) {
                    $('#daterange-btn span').html(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
                    points_table.ajax.url( base_url + '?start_date=' + start.format('YYYY-MM-DD') +
                        '&end_date=' + end.format('YYYY-MM-DD')+ '&business_location=' + $('#business_location').val() ).load();
                }
            );
            daterange_btn.on('cancel.daterangepicker', function(ev, picker) {
                points_table.ajax.url( base_url + '?business_location=' + $('#business_location').val() ).load();
                $('#daterange-btn span').html('<i class="fa fa-calendar"></i> {{ __("messages.filter_by_date") }}');
            });

            $( "#business_location" ).change(function() {
                $('#bussiness_loc_form').submit();
            });
        });
    </script>
@endsection