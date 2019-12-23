@extends('layouts.app')
@section('title', __( 'delivery.new_orders'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1>@lang( 'delivery.new_orders')
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
            	<table class="table table-bordered table-striped ajax_view" id="new_order_table">
            		<thead>
            			<tr>
            				<th>@lang('messages.date')</th>
                            <th>@lang('delivery.uid')</th>
    						<th>@lang('delivery.location')</th>
                            <th>@lang('delivery.user_name')</th>
                            <th>@lang('delivery.user_phone')</th>
    						<th>@lang('delivery.user_email')</th>
                            <th>@lang('delivery.area')</th>
                            <th>@lang('delivery.area_address')</th>
                            <th>@lang('messages.action')</th>
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
<script src="https://js.pusher.com/4.3/pusher.min.js"></script>
<script type="text/javascript">
    $(document).ready( function(){
        //Date range as a button
        $('#delivery_date_filter').daterangepicker(
            dateRangeSettings,
            function (start, end) {
                $('#delivery_date_filter span').html(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
                new_order_table.ajax.reload();
            }
        );
        $('#delivery_date_filter').on('cancel.daterangepicker', function(ev, picker) {
            $('#delivery_date_filter').html('<i class="fa fa-calendar"></i> {{ __("messages.filter_by_date") }}');
            new_order_table.ajax.reload();
        });

        new_order_table = $('#new_order_table').DataTable({
            processing: true,
            serverSide: true,
            aaSorting: [[0, 'desc']],
            "ajax": {
                "url": "/delivery/new",
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
                { data: 'action', name: 'action'}
            ],
            columnDefs: [ {
                "targets": 8,
                "orderable": false,
                "searchable": false
            } ],
        });

        $('.btn_action').click(function(e) {
            // Do something
            e.stopPropagation();
        });
    });

    function confirm_new_order(uid, pay_method) {
        if (pay_method === 'Cash') {
            confirm_after_check_connection(uid);
        } else {
            document.location.href = '/delivery/confirm/' + uid;
        }
    }

    function confirm_after_check_connection(uid) {
        toastr.info('Checking connection...');
        let connected = false; // if connected this will be updated to true
        $.ajax({
            url: '/pos_ping/' + uid,
            method: 'GET',
            data: {},
            complete: function () {
                console.log('Ping successfully');
            }
        });
        let pusher = new Pusher('01b908ea43142bcf4b39', {   //ENV('PUSHER_APP_KEY')
            cluster: 'us2',  // ENV('PUSHER_CLUSTER')
            forceTLS: true
        });

        let channel = pusher.subscribe('pos-channel'); // ENV('PUSHER_CHANNEL')
        channel.bind('pos-event', function(data) {  // ENV('PUSHER_PING_EVENT')

            if(data.message === ('pos-pong-' + uid)) {
                connected = true;
                toastr.success('Connection successfully established. Processing...');
                document.location.href = '/delivery/confirm/' + uid;
                // cash_clicked = false;
            }

        });

        setTimeout(function () {
            if (!connected) toastr.info('Reconnecting...');
        }, 5000);
        setTimeout(function () {
            if (!connected) toastr.warning('Connection unsuccessful, please check mobile app.');
            pusher.disconnect();
            // cash_clicked = false;
        }, 10000);
    
    }
</script>

@endsection