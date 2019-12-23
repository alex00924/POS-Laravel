@extends('layouts.app')
@section('title', __( 'delivery.uncomplete_orders'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1>@lang( 'delivery.uncomplete_orders')
        <small></small>
    </h1>
</section>

<!-- Main content -->
<section class="content no-print">
	<div class="box">
        <div class="box-body">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="form-group">
                            <div class="input-group">
                              <button type="button" class="btn btn-primary" id="delivery_date_filter">
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
            	<table class="table table-bordered table-striped ajax_view" id="uncomplete_order_table">
            		<thead>
            			<tr>
            				<th>@lang('messages.date')</th>
                            <th>@lang('delivery.uid')</th>
    						<th>@lang('delivery.order_no')</th>
                            <th>@lang('delivery.user_name')</th>
                            <th>@lang('delivery.user_phone')</th>
    						<th>@lang('delivery.user_email')</th>
                            <th>@lang('delivery.area')</th>
                            <th>@lang('delivery.area_address')</th>
            			</tr>
            		</thead>
            	</table>
                </div>
        </div>
    </div>
</section>
<!-- /.content -->
<div class="modal fade payment_modal" tabindex="-1" role="dialog" 
    aria-labelledby="gridSystemModalLabel">
</div>

<div class="modal fade edit_payment_modal" tabindex="-1" role="dialog" 
    aria-labelledby="gridSystemModalLabel">
</div>

@stop

@section('javascript')
<script type="text/javascript">
$(document).ready( function(){
    //Date range as a button
    $('#delivery_date_filter').daterangepicker(
        dateRangeSettings,
        function (start, end) {
            $('#delivery_date_filter span').html(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
            uncomplete_order_table.ajax.reload();
        }
    );
    $('#delivery_date_filter').on('cancel.daterangepicker', function(ev, picker) {
        $('#delivery_date_filter').html('<i class="fa fa-calendar"></i> {{ __("messages.filter_by_date") }}');
        uncomplete_order_table.ajax.reload();
    });

    uncomplete_order_table = $('#uncomplete_order_table').DataTable({
        processing: true,
        serverSide: true,
        aaSorting: [[0, 'desc']],
        "ajax": {
            "url": "/delivery/uncomplete",
            "data": function ( d ) {
                var start = $('#delivery_date_filter').data('daterangepicker').startDate.format('YYYY-MM-DD');
                var end = $('#delivery_date_filter').data('daterangepicker').endDate.format('YYYY-MM-DD');
                d.start_date = start;
                d.end_date = end;
            }
        },
        columns: [
            { data: 'order_date', name: 'delivery_in_carts.created_at'  },
            { data: 'order_no', name: 'delivery_in_carts.uid'},
            { data: 'business_location', name: 'bl.name'},
            { data: 'user_name', name: 'user_name'},
            { data: 'user_phone', name: 'user_phone'},
            { data: 'user_email', name: 'user_email'},
            { data: 'order_area', name: 'order_area'},
            { data: 'order_area_address', name: 'order_area_address'},
        ],
    });

    $('.btn_action').click(function(e) {
        // Do something
        e.stopPropagation();
    });
});
</script>

@endsection