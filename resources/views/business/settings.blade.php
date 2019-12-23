@extends('layouts.app')
@section('title', __('business.business_settings'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('business.business_settings')</h1>
    <!-- <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol> -->
</section>

<!-- Main content -->
<section class="content">
{!! Form::open(['url' => action('BusinessController@getBusinessSettings'), 'method' => 'get', 'id' => 'bussiness_loc_form']) !!}
    <div class="row">
        <div class="col-xs-12" style='margin-bottom: 10px'>
            {!! Form::label('business_location', __('business.business_locations') . ':' ) !!}
            {!! Form::select('business_location', $locations, $location, ['class' => 'form-control', 'style' => 'width: 100%;' ]); !!}
        </div>
    </div>
{!! Form::close() !!}

{!! Form::open(['url' => action('BusinessController@postBusinessSettings'), 'method' => 'post', 'id' => 'bussiness_edit_form',
           'files' => true ]) !!}
    <div class="row">
        <div class="col-xs-12">
       <!--  <pos-tab-container> -->
        <div class="col-xs-12 pos-tab-container">
            <div class="col-lg-2 col-md-2 col-sm-2 col-xs-2 pos-tab-menu">
                <div class="list-group">
                    <a href="#" class="list-group-item text-center active">@lang('business.business')</a>
                    <a href="#" class="list-group-item text-center">@lang('business.tax') @show_tooltip(__('tooltip.business_tax'))</a>
                    <a href="#" class="list-group-item text-center">@lang('business.product')</a>
                    <a href="#" class="list-group-item text-center">@lang('business.sale')</a>
                    <a href="#" class="list-group-item text-center">@lang('purchase.purchases')</a>
                    @if(!config('constants.disable_expiry', true))
                    <a href="#" class="list-group-item text-center">@lang('business.dashboard')</a>
                    @endif
                    <a href="#" class="list-group-item text-center">@lang('business.system')</a>
                    <a href="#" class="list-group-item text-center">@lang('lang_v1.prefixes')</a>
                    <a href="#" class="list-group-item text-center">@lang('sale.pos_sale')</a>
                    <a href="#" class="list-group-item text-center">@lang('lang_v1.modules')</a>
                    @cannot('superadmin')
                    <a href="#" class="list-group-item text-center">@lang('lang_v1.braintree')</a>
                    @endcan
                    @if($business->is_delivery && auth()->user()->can('access_delivery'))
                    <a href="#" id='delivery_tab' class="list-group-item text-center">@lang('business.delivery')</a>
                    @endif
                </div>
            </div>
            <div class="col-lg-10 col-md-10 col-sm-10 col-xs-10 pos-tab">
                <!-- tab 1 start -->
                @include('business.partials.settings_business')
                <!-- tab 1 end -->
                <!-- tab 2 start -->
                @include('business.partials.settings_tax')
                <!-- tab 2 end -->
                <!-- tab 3 start -->
                @include('business.partials.settings_product')
                <!-- tab 3 end -->
                <!-- tab 4 start -->
                @include('business.partials.settings_sales')
                <!-- tab 4 end -->
                <!-- tab 5 start -->
                @include('business.partials.settings_purchase')
                <!-- tab 5 end -->
                <!-- tab 6 start -->
                @if(!config('constants.disable_expiry', true))
                    @include('business.partials.settings_dashboard')
                @endif
                <!-- tab 6 end -->
                <!-- tab 7 start -->
                @include('business.partials.settings_system')
                <!-- tab 7 end -->
                <!-- tab 8 start -->
                @include('business.partials.settings_prefixes')
                <!-- tab 8 end -->
                <!-- tab 9 start -->
                @include('business.partials.settings_pos')
                <!-- tab 9 end -->
                <!-- tab 10 start -->
                @include('business.partials.settings_modules')
                <!-- tab 10 end -->
                @cannot('superadmin')
                <!-- tab 11 start -->
                @include('business.partials.settings_braintree')
                <!-- tab 11 end -->
                @endcan
                <!-- tab 12 start -->
                @if($business->is_delivery && auth()->user()->can('access_delivery'))
                @include('business.partials.settings_delivery')
                @endif
                <!-- tab 12 end -->
            </div>
        </div>
        <!--  </pos-tab-container> -->
        </div>
    </div>
    <input type='hidden' id='is_delivery' name='is_delivery'>
    <input type='hidden' id='business_location_id' name='business_location_id'>
    <div class="row">
        <div class="col-sm-12">
            <button class="btn btn-danger pull-right" onclick='submit_form()'>@lang('business.update_settings')</button>
        </div>
    </div>
{!! Form::close() !!}
</section>
<!-- /.content -->

@endsection

@section('javascript')
<script type="text/javascript">
    var is_delivery = {!! $business->is_delivery; !!};
    
    @if($business->is_delivery) 
        var delivery_all = {!! $delivery->is_all; !!};
        var delivery_products = {!! $delivery->product_ids; !!};
        var delivery_areas = {!! $delivery->areas !!};
    @endif

        $(document).ready(function(){
            console.log($('.braintree_env').val());
            if ($('.braintree_env').val() == 'none') {
                $('.merchant_account_id').attr('disabled', true);
                $('.merchant_id').attr('disabled', true);
                $('.public_key').attr('disabled', true);
                $('.private_key').attr('disabled', true);
                $('.minimum_amount').attr('disabled', true);
            }
            
            @if(!$delivery->enabled)
                $('#delivery_tab').hide();
            @endif

            $('.enable_delivery').change(function() {
                if(this.checked) 
                    $('#delivery_tab').show();
                else
                    $('#delivery_tab').hide();
            });

            @if($business->is_delivery) 
            
                @if($delivery->is_minimum == 0)
                    $("#delivery_minimum").attr('disabled', 'disabled');
                @endif

                @if($delivery->is_all == 1)
                    $('#table_div').hide();
                @endif

                $(".is_minimum").change( function(){
                    if( $(this).is(':checked') ) 
                        $("#delivery_minimum").removeAttr('disabled');
                    else
                        $("#delivery_minimum").attr('disabled', 'disabled');
                });

                $("#is_all").change( function(){
                    if( $(this).is(':checked') ) 
                    {
                        $('#table_div').hide();
                    }
                    else
                    {
                        $('#table_div').show();
                    }
                });


                for( var i = 0 ; i < delivery_areas.length; i ++ )
                {
                    add_area(delivery_areas[i]);
                }
                if( delivery_areas == '' || delivery_areas.length < 1 )
                    add_area("");

                col_targets = [0, 1, 9];
                col_sort = [2, 'asc'];

                var product_table = $('#product_table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: '/products/generate_table',
                    columnDefs: [ {
                        "targets": col_targets,
                        "orderable": false,
                        "searchable": false
                    } ],
                    aaSorting: [col_sort],
                    columns: [
                        { data: 'mass_delete'  },
                        { data: 'product_image', name: 'products.image'  },
                        { data: 'product', name: 'products.name'  },
                        { data: 'type', name: 'products.type'},
                        { data: 'category', name: 'c1.name'},
                        { data: 'sub_category', name: 'c2.name'},
                        { data: 'unit', name: 'units.actual_name'},
                        { data: 'brand', name: 'brands.name'},
                        { data: 'tax', name: 'tax_rates.name'},
                        { data: 'sku', name: 'products.sku'},
                        ],
                        createdRow: function( row, data, dataIndex ) {
                            if($('input#is_rack_enabled').val() == 1){
                                    target_col = 1;
                                $( row ).find('td:eq('+target_col+') div').prepend('<i style="margin:auto;" class="fa fa-plus-circle text-success cursor-pointer no-print rack-details" title="' + LANG.details + '"></i>&nbsp;&nbsp;');
                            }
                                $( row ).find('td:eq(0)').attr('class', 'selectable_td');
                            if( delivery_products.includes(parseInt(data.id)) )
                            {
                                $(row).addClass('selected');
                                $( row ).find('td:eq(0) input').prop('checked', true);
                            }
                        }
                });

                $('#product_table tbody').on( 'click', 'tr', function () {
                    if( $(this).hasClass('selected'))
                    {
                        $( this ).find('td:eq(0) input').prop('checked', false);
                        $(this).removeClass('selected');
                        var index = delivery_products.indexOf(parseInt($( this ).find('td:eq(0) input').val()));
                        if (index > -1) {
                            delivery_products.splice(index, 1);
                        }
                    }
                    else
                    {
                        $(this).addClass('selected');
                        $( this ).find('td:eq(0) input').prop('checked', true);
                        delivery_products.push(parseInt($( this ).find('td:eq(0) input').val()));
                    }
                });
            @endif

        });
        $('.braintree_env').on('change', function(e) {
            if (e.target.value == 'none') {
                $('.merchant_account_id').attr('disabled', true);
                $('.merchant_id').attr('disabled', true);
                $('.public_key').attr('disabled', true);
                $('.private_key').attr('disabled', true);
                $('.minimum_amount').attr('disabled', true);
            } else {
                $('.merchant_account_id').attr('disabled', false);
                $('.merchant_id').attr('disabled', false);
                $('.public_key').attr('disabled', false);
                $('.private_key').attr('disabled', false);
                $('.minimum_amount').attr('disabled', false);
            }
        });
        $(".minimum_amount").attr({ "min" : 1  });

        
        function submit_form()
        {
            @if($business->is_delivery) 
                delivery_areas = [];
                var areas = $('[name=delivery_area]');
                for(var i = 0 ; i < areas.length ; i ++ )
                {
                    if( areas[i].value !== '' )
                        delivery_areas.push('"' + areas[i].value + '"');
                }
                $('#delivery_areas').val('[' + delivery_areas + ']');
                $('#delivery_products').val('[' + delivery_products + ']');
            @endif
            $('#is_delivery').val({!!$business->is_delivery;!!});
            $('#business_location_id').val({!!$location;!!});
            $('#bussiness_edit_form').submit();
        }

        function add_area(area_name)
        {
            var new_div = " <div class='col-sm-4'>";
            new_div += '<input class="form-control merchant_account_id valid" style="margin-bottom:5px" name="delivery_area" type="text" value="' + area_name + '">';
            new_div += "</div>";
            $('#btn_add_div').before(new_div);
        }

        $( "#business_location" ).change(function() {
            $('#bussiness_loc_form').submit();
        });
    </script>
@endsection
