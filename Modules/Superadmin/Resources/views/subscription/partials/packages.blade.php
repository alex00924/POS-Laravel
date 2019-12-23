@foreach ($packages as $package)
    <div class="col-md-4">
    	
		<div class="box box-success hvr-grow-shadow">
			<div class="box-header with-border text-center">
				<h2 class="box-title">{{$package->name}}</h2>
			</div>
			
			<!-- /.box-header -->
			<div class="box-body text-center">

				<i class="fa fa-check text-success"></i>
				@if($package->location_count == 0)
					@lang('superadmin::lang.unlimited')
				@else
					{{$package->location_count}}
				@endif

				@lang('business.business_locations')
				<hr/>

				<i class="fa fa-check text-success"></i>
				@if($package->user_count == 0)
					@lang('superadmin::lang.unlimited')
				@else
					{{$package->user_count}}
				@endif

				@lang('superadmin::lang.users')
				<hr/>

				<i class="fa fa-check text-success"></i>
				@if($package->product_count == 0)
					@lang('superadmin::lang.unlimited')
				@else
					{{$package->product_count}}
				@endif

				@lang('superadmin::lang.products')
				<hr/>

				<i class="fa fa-check text-success"></i>
				@if($package->invoice_count == 0)
					@lang('superadmin::lang.unlimited')
				@else
					{{$package->invoice_count}}
				@endif

				@lang('superadmin::lang.invoices')
				<hr/>

				@if($package->trial_days != 0)
					<i class="fa fa-check text-success"></i>
					{{$package->trial_days}} @lang('superadmin::lang.trial_days')
					<hr/>
				@endif
				
				@if($package->is_delivery != 0)
					<i class="fa fa-check text-success"></i>
					 @lang('superadmin::lang.is_delivery')
					<hr/>
				@endif
				

				<h3 class="text-center">

					@if($package->price != 0)
						<span class="display_currency" data-currency_symbol="true">
							{{$package->price}}
						</span>

						<small>
							/ {{$package->interval_count}} {{ucfirst($package->interval)}}
						</small>
					@else
						@lang('superadmin::lang.free_for_duration', ['duration' => $package->interval_count . ' ' . ucfirst($package->interval)])
					@endif
				</h3>
			</div>
			<!-- /.box-body -->

			<div class="box-footer text-center">
				@if(isset($action_type) && $action_type == 'register')
					<a href="{{ route('business.getRegister') }}?package={{$package->id}}" 
					class="btn btn-block btn-success">
	    				@if($package->price != 0)
	    					@lang('superadmin::lang.register_subscribe')
	    				@else
	    					@lang('superadmin::lang.register_free')
	    				@endif
    				</a>
				@else
					<a href="{{action('\Modules\Superadmin\Http\Controllers\SubscriptionController@pay', [$package->id])}}" 
					class="btn btn-block btn-success">
	    				@if($package->price != 0)
	    					@lang('superadmin::lang.pay_and_subscribe')
	    				@else
	    					@lang('superadmin::lang.subscribe')
	    				@endif
    				</a>
				@endif

    			{{$package->description}}
			</div>
		</div>
		<!-- /.box -->
    </div>
@endforeach