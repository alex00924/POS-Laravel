@extends('layouts.app')
@section('title', __('home.home'))

@section('css')
    {!! Charts::styles(['highcharts']) !!}
@endsection

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>{{ __('home.welcome_message', ['name' => Session::get('user.first_name')]) }}
    </h1>
</section>
@if(auth()->user()->can('dashboard.data'))
<!-- Main content -->
<section class="content">
	<div class="row">
		<div class="col-md-12 col-xs-12">
			<div class="btn-group pull-right" data-toggle="buttons">
				<label class="btn btn-info active">
    				<input type="radio" name="date-filter"
    				data-start="{{ date('Y-m-d') }}" 
    				data-end="{{ date('Y-m-d') }}"
    				checked> {{ __('home.today') }}
  				</label>
  				<label class="btn btn-info">
    				<input type="radio" name="date-filter"
    				data-start="{{ $date_filters['this_week']['start']}}" 
    				data-end="{{ $date_filters['this_week']['end']}}"
    				> {{ __('home.this_week') }}
  				</label>
  				<label class="btn btn-info">
    				<input type="radio" name="date-filter"
    				data-start="{{ $date_filters['this_month']['start']}}" 
    				data-end="{{ $date_filters['this_month']['end']}}"
    				> {{ __('home.this_month') }}
  				</label>
  				<label class="btn btn-info">
    				<input type="radio" name="date-filter" 
    				data-start="{{ $date_filters['this_fy']['start']}}" 
    				data-end="{{ $date_filters['this_fy']['end']}}" 
    				> {{ __('home.this_fy') }}
  				</label>
            </div>
		</div>
	</div>
	<br>
	<div class="row">
    	<div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
	      <div class="info-box">
	        <span class="info-box-icon bg-aqua"><i class="ion ion-cash"></i></span>

	        <div class="info-box-content">
	          <span class="info-box-text">{{ __('home.total_purchase') }}</span>
	          <span class="info-box-number total_purchase"><i class="fa fa-refresh fa-spin fa-fw margin-bottom"></i></span>
	        </div>
	        <!-- /.info-box-content -->
	      </div>
	      <!-- /.info-box -->
	    </div>
	    <!-- /.col -->
			<div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
	      <div class="info-box">
	        <span class="info-box-icon bg-aqua"><i class="ion ion-ios-cart-outline"></i></span>

	        <div class="info-box-content">
	          <span class="info-box-text">{{ __('home.total_sell') }}</span>
	          <span class="info-box-number total_sell"><i class="fa fa-refresh fa-spin fa-fw margin-bottom"></i></span>
	        </div>
	        <!-- /.info-box-content -->
	      </div>
	      <!-- /.info-box -->
	    </div>
			<!-- /.col -->
			<div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
	      <div class="info-box">
	        <span class="info-box-icon bg-aqua"><i class="ion ion-ios-cart-outline"></i></span>

	        <div class="info-box-content">
	          <span class="info-box-text">{{ __('home.total_bullet') }}</span>
	          <span class="info-box-number total_bullet"><i class="fa fa-refresh fa-spin fa-fw margin-bottom"></i></span>
	        </div>
	        <!-- /.info-box-content -->
	      </div>
	      <!-- /.info-box -->
	    </div>
			<!-- /.col -->
	    <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
	      <div class="info-box">
	        <span class="info-box-icon bg-yellow" style="position: relative;">
	        	<i class="fa fa-dollar"></i>
				<i class="fa fa-exclamation"></i>
	        </span>

	        <div class="info-box-content">
	          <span class="info-box-text">{{ __('home.purchase_due') }}</span>
	          <span class="info-box-number purchase_due"><i class="fa fa-refresh fa-spin fa-fw margin-bottom"></i></span>
	        </div>
	        <!-- /.info-box-content -->
	      </div>
	      <!-- /.info-box -->
	    </div>
	    <!-- /.col -->
	    <!-- fix for small devices only -->
	    <!-- <div class="clearfix visible-sm-block"></div> -->
	    <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
	      <div class="info-box">
	        <span class="info-box-icon bg-yellow">
	        	<i class="ion ion-ios-paper-outline"></i>
	        	<i class="fa fa-exclamation"></i>
	        </span>

	        <div class="info-box-content">
	          <span class="info-box-text">{{ __('home.invoice_due') }}</span>
	          <span class="info-box-number invoice_due"><i class="fa fa-refresh fa-spin fa-fw margin-bottom"></i></span>
	        </div>
	        <!-- /.info-box-content -->
	      </div>
	      <!-- /.info-box -->
	    </div>
	    <!-- /.col -->
  	</div>
	<br>
	<!-- Request redeem bullets start -->
	@if(count($requested_redeem) > 0)
	<div class="row">
		<div class="col-md-12">
			<div class="alert alert-info padding-10">
				<div class="width-100" style="font-size: 22px">Newest 3 requests</div>
				<ul>
					@foreach($requested_redeem as $redeem)
						<li class="requested-redeem" data-id="{{$redeem->id}}">
							<a href="javascript:void(0)" class="approve_redeem"
							   data-id="{{$redeem->id}}"
							   data-name="{{$redeem->location->name}}"
							   data-points="{{$redeem->points}}">
								<span class="business-name">{{$redeem->location->name}}</span>
								<span class="redeem-message">requested
								<span class="redeem-points">{{$redeem->points}}</span>
								bullets for redeem.
							</span>
							</a>
						</li>
					@endforeach
				</ul>
			</div>
		</div>
	</div>
	@endif
	<!-- Request redeem bullets end -->
  	<br>
  	<!-- sales chart start -->
  	<div class="row">
  		<div class="col-sm-12">
  			<div class="box box-primary">
  				<div class="box-header">
         			<h3 class="box-title">{{ __('home.sells_last_30_days') }}</h3>
         		</div>
	            <div class="box-body">
	              {!! $sells_chart_1->html() !!}
	            </div>
	            <!-- /.box-body -->
          	</div>
  		</div>
  	</div>

  	<div class="row">
  		<div class="col-sm-12">
  			<div class="box box-primary">
  				<div class="box-header">
         			<h3 class="box-title">{{ __('home.sells_current_fy') }}</h3>
         		</div>
	            <div class="box-body">
	              {!! $sells_chart_2->html() !!}
	            </div>
	            <!-- /.box-body -->
          	</div>
  		</div>
  	</div>
  	<!-- sales chart end -->

  	<!-- products less than alert quantity -->
  	<div class="row">
  		<div class="col-sm-6">
  			<div class="box box-warning">
  				<div class="box-header">
  					<i class="fa fa-exclamation-triangle text-yellow" aria-hidden="true"></i>
         			<h3 class="box-title">{{ __('home.product_stock_alert') }} @show_tooltip(__('tooltip.product_stock_alert')) </h3>
         		</div>
	            <div class="box-body">
	              <table class="table table-bordered table-striped" id="stock_alert_table">
            		<thead>
            			<tr>
            				<th>@lang( 'sale.product' )</th>
            				<th>@lang( 'business.location' )</th>
                            <th>@lang( 'report.current_stock' )</th>
            			</tr>
            		</thead>
            	</table>
	            </div>
	            <!-- /.box-body -->
          	</div>
  		</div>
  		<div class="col-sm-6">
  			<div class="box box-warning">
  				<div class="box-header">
  					<i class="fa fa-exclamation-triangle text-yellow" aria-hidden="true"></i>
         			<h3 class="box-title">{{ __('home.payment_dues') }} @show_tooltip(__('tooltip.payment_dues'))</h3>
         		</div>
	            <div class="box-body">
	              <table class="table table-bordered table-striped" id="payment_dues_table">
            		<thead>
            			<tr>
            				<th>@lang( 'purchase.supplier' )</th>
            				<th>@lang( 'purchase.ref_no' )</th>
                            <th>@lang( 'home.due_amount' )</th>
            			</tr>
            		</thead>
            	</table>
	            </div>
	            <!-- /.box-body -->
          	</div>
  		</div>
      <div class="clearfix"></div>
      @if(session('business.enable_product_expiry') == 1)
        <div class="col-sm-12">
          <div class="box box-warning">
            <div class="box-header">
              <i class="fa fa-exclamation-triangle text-yellow" aria-hidden="true"></i>
                <h3 class="box-title">{{ __('home.stock_expiry_alert') }} @show_tooltip( __('tooltip.stock_expiry_alert', [ 'days' =>session('business.stock_expiry_alert_days', 30) ]) )</h3>
            </div>
                <div class="box-body">
                  <input type="hidden" id="stock_expiry_alert_days" value="{{ \Carbon::now()->addDays(session('business.stock_expiry_alert_days', 30))->format('Y-m-d') }}">
                  <table class="table table-bordered table-striped" id="stock_expiry_alert_table">
                  <thead>
                    <tr>
                      <th>@lang('business.product')</th>
                      <th>@lang('business.location')</th>
                      <th>@lang('report.stock_left')</th>
                      <th>@lang('product.expires_in')</th>
                    </tr>
                  </thead>
                </table>
                </div>
                <!-- /.box-body -->
          </div>
        </div>
      @endif
  	</div>

	<!-- approve redeem modal -->
	@can('superadmin')
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
	@endcan
	<!-- approve redeem modal end -->
</section>
<!-- /.content -->
@stop
@section('javascript')
    <script src="{{ asset('js/home.js?v=' . $asset_v) }}"></script>
    {!! Charts::assets(['highcharts']) !!}
    {!! $sells_chart_1->script() !!}
    {!! $sells_chart_2->script() !!}
@endif
@endsection

