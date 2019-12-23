<div class="pos-tab-content">
    <!-- Braintree Info -->
    <div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('item_addition_method', 'Enviroment:*') !!}
                {!! Form::select('braintree_env', array('none'=> 'Don\'t Use', 'sandbox'=>'SandBox', 'production'=>'Live'), $business->braintree_env, ['class' => 'form-control braintree_env']); !!}
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('item_addition_method', __('lang_v1.braintree_merchant_account_id') . ':*') !!}
                {!! Form::text('braintree_merchant_account_id', $business->braintree_merchant_account_id, ['class' => 'form-control merchant_account_id']); !!}
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('item_addition_method', __('lang_v1.braintree_merchant_id') . ':*') !!}
                {!! Form::text('braintree_merchant_id', $business->braintree_merchant_id, ['class' => 'form-control merchant_id']); !!}
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('item_addition_method', __('lang_v1.braintree_public_key') . ':*') !!}
                {!! Form::text('braintree_public_key', $business->braintree_public_key, ['class' => 'form-control public_key']); !!}
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('item_addition_method', __('lang_v1.braintree_private_key') . ':*') !!}
                {!! Form::text('braintree_private_key', $business->braintree_private_key, ['class' => 'form-control private_key']); !!}
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('item_addition_method', __('lang_v1.minimum_amount') . ':*') !!}
                {!! Form::text('minimum_amount', $business->minimum_amount, ['class' => 'form-control minimum_amount required']); !!}
            </div>
        </div>
    </div>
</div>