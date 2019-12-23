$(document).ready(function(){

	var start = $('input[name="date-filter"]:checked').data('start');
	var end = $('input[name="date-filter"]:checked').data('end');
	update_statistics(start, end);
	$(document).on('change', 'input[name="date-filter"]', function(){
		var start = $('input[name="date-filter"]:checked').data('start');
		var end = $('input[name="date-filter"]:checked').data('end');
		update_statistics(start, end);
	});

	//atock alert datatables
	var stock_alert_table = $('#stock_alert_table').DataTable({
					processing: true,
					serverSide: true,
					ordering: false,
					searching: false,
					dom: 'tirp',
					buttons:[],
					ajax: '/home/product-stock-alert'
			    });
	//payment dues datatables
	var payment_dues_table = $('#payment_dues_table').DataTable({
					processing: true,
					serverSide: true,
					ordering: false,
					searching: false,
					dom: 'tirp',
					buttons:[],
					ajax: '/home/payment-dues',
					"fnDrawCallback": function (oSettings) {
			            __currency_convert_recursively($('#payment_dues_table'));
			        }
			    });

	//Stock expiry report table
    stock_expiry_alert_table = $('#stock_expiry_alert_table').DataTable({
                    processing: true,
					serverSide: true,
					searching: false,
					dom: 'tirp',
                    "ajax": {
                        "url": "/reports/stock-expiry",
                        "data": function ( d ) {
                            d.exp_date_filter = $('#stock_expiry_alert_days').val();
                        }
                    },
                    "order": [[ 3, "asc" ]],
                    columns: [
                        {data: 'product', name: 'p.name'},
                        {data: 'location', name: 'l.name'},
                        {data: 'stock_left', name: 'stock_left'},
                        {data: 'exp_date', name: 'exp_date'},
                    ],
                    "fnDrawCallback": function (oSettings) {
                        __show_date_diff_for_human($('#stock_expiry_alert_table'));
                    }
                });
});

function update_statistics( start, end ){
	var data = { start: start, end: end };
	//get purchase details
	var loader = '<i class="fa fa-refresh fa-spin fa-fw margin-bottom"></i>';
	var loader_points = '<i class="fa fa-spin fa-spinner" style="position: absolute; bottom: 0"></i>';
	$('.total_purchase').html(loader);
	$('.purchase_due').html(loader);
	$('.total_sell').html(loader);
	$('.total_bullet').html(loader);
	$('.invoice_due').html(loader);
	$('.points-font').html(loader_points);
	$.ajax({
		method: "POST",
		url: '/home/get-purchase-details',
		dataType: "json",
		data: data,
		success: function(data){
			$('.total_purchase').html(__currency_trans_from_en(data.total_purchase_inc_tax, true ));
			$('.purchase_due').html( __currency_trans_from_en(data.purchase_due, true));
		}
	});
	//get sell details
	$.ajax({
		method: "POST",
		url: '/home/get-sell-details',
		dataType: "json",
		data: data,
		success: function(data){
			$('.total_sell').html(__currency_trans_from_en(data.total_sell_exc_tax, true ));
			$('.total_bullet').html(data.total_bullet);
			$('.invoice_due').html( __currency_trans_from_en(data.invoice_due, true));
		}
	});
}
// redeem modal setting
$(document).on('click', '.approve_redeem', function () {
	let redeem_id = $(this).data('id');
	let points = $(this).data('points');
	let business_name = $(this).data('name');

	$('.modal-business-name').html(business_name);
	$('.modal-redeem-points').html(points);
	$('.button-redeem').data('id', redeem_id);

	$('#approve_redeem_modal').modal();
});
$(document).on('click', '.button-redeem', function () {
	let redeem_id = $(this).data('id');
	let redeem_mode = $(this).data('method');
	if (redeem_mode === 'cancel') {
		$('#approve_redeem_modal').modal('hide');
		return;
	}
	let redeem_row = 'li.requested-redeem[data-id=' + redeem_id + ']';
	let url = redeem_mode === 'approve' ? '/superadmin/approve-redeem/' : '/superadmin/reject-redeem/';
	$.ajax({
		url: url + redeem_id,
		method: 'get',
		data:{},
		success: function (result) {
			toastr[result.status](result.message);
			$(redeem_row).remove();
		},
		complete: function () {
			$('#approve_redeem_modal').modal('hide');
		}
	});
});

