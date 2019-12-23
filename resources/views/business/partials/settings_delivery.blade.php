<div class="pos-tab-content">
    <div class='delivery_env'>
        <div class="col-sm-12" style='padding:0px'>
            <div style='padding-left:15px'>
                {!! Form::label('delivery_area', __('lang_v1.delivery_area')) !!}
            </div>
            <div class="form-group">
                <div class="input-group" style='padding-right:15px' id='btn_add_div'>
                    <span class="input-group-btn" style='text-align:right'>
                        <button type="button" onclick='add_area("")' class="btn btn-default bg-white btn-flat btn-modal"><i class="fa fa-plus-circle text-primary fa-lg"></i>&nbsp;Add Area</button>
                    </span>
                </div>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('delivery_charge', __('lang_v1.delivery_charge') . ':*') !!}
                {!! Form::text('delivery_charge', $delivery->charge, ['class' => 'form-control required']); !!}
            </div>
        </div>
        
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('delivery_tax',  __('lang_v1.delivery_applicable_tax')) !!}
                {!! Form::select('delivery_tax', $tax_rates, $delivery->tax, ['class' => 'form-control']); !!}
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('delivery_taxtype',  __('lang_v1.delivery_tax_type')) !!}
                {!! Form::select('delivery_tax_type', ['inclusive'=>'inclusive', 'exclusive'=>'exclusive'], $delivery->tax_type, ['class' => 'form-control']); !!}
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('delivery_email',  __('lang_v1.delivery_email')).':*' !!}
                {!! Form::text('delivery_email', $delivery->email, ['class' => 'form-control ']); !!}
            </div>
        </div>

        <div class="col-sm-4">
            <div class="checkbox">
                <br>
                <label>
                    {!! Form::checkbox('is_minimum', 1, $delivery->is_minimum , 
                    [ 'class' => 'is_minimum']); !!} {{ __( 'lang_v1.delivery_is_minimum' ) }}
                </label>
            </div>
        </div>

        <div class="col-sm-4">
            <div class='form-group'>
                {!! Form::label('delivery_minimum',  __('lang_v1.delivery_minimum')).':*' !!}
                {!! Form::text('delivery_minimum', $delivery->minimum, ['class' => 'form-control required delivery_minimum']); !!}
            </div>
        </div>

        <div class='col-sm-12'>
            <div class="checkbox">
                <label>
                {!! Form::checkbox('is_all', 1, $delivery->is_all , 
                ['id'=>'is_all']); !!} {{ __( 'lang_v1.delivery_is_all' ) }}
                </label>
            </div>
        </div>

        <div class='col-sm-12' id='table_div'>
            <div class='box' style='padding-top:10px'>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped ajax_view table-text-center" id="product_table" style='width:100%'>
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="select-all-row"></th>
                                <th>&nbsp;</th>
                                <th>@lang('sale.product')</th>
                                <th>@lang('product.product_type')</th>
                                <th>@lang('product.category')</th>
                                <th>@lang('product.sub_category')</th>
                                <th>@lang('product.unit')</th>
                                <th>@lang('product.brand')</th>
                                <th>@lang('product.tax')</th>
                                <th>@lang('product.sku')</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <input type='hidden' id='delivery_products' name='delivery_products'>
    <input type='hidden' id='delivery_areas' name='delivery_areas'>
</div>

