<?php
	/**
	 * Created by PhpStorm.
	 * User: User
	 * Date: 10/5/2018
	 * Time: 8:43 PM
	 */
	?>
<div class="box box-widget @if($pos_settings['hide_product_suggestion'] == 0) collapsed-box @endif">
    <div class="box-header with-border">
        <h3 class="box-title">@lang('sale.recent_mobile_transactions')</h3>
        <div class="form-group" style="width: 100% !important">
            <div class="input-group">
									<span class="input-group-addon">
										<i class="fa fa-mobile"></i>
									</span>

                {!! Form::text('mobile_invoice_no', null,
                ['class' => 'form-control mousetrap', 'id' => 'mobile_invoice_no', 'placeholder' => __('lang_v1.search_mobile_transaction'),
                'onkeypress' => 'get_mobile_transaction_by_invoice_no(event)']); !!}
            </div>
        </div>

        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="collapse" id="mobile_transaction_box_btn">
                @if($pos_settings['hide_product_suggestion'] == 0)
                    <i class="fa fa-plus"></i>
                @else
                    <i class="fa fa-minus"></i>
                @endif
            </button>
        </div>

        <!-- /.box-tools -->
    </div>
    <!-- /.box-header -->

    <div class="box-body">
        <div class="nav-tabs-custom">

            <div class="tab-content">
                <div class="tab-pane active" id="mobile_transactions">
                </div>
            </div>
        </div>
    </div>
    <!-- /.box-body -->
</div>
