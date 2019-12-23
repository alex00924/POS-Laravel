<div class="modal-dialog modal-xl no-print" role="document">
  <div class="modal-content">
    <div class="modal-header">
    <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    <h4 class="modal-title" id="modalTitle"> @lang('delivery.delivery_details')</h4>
</div>
<div class="modal-body">
    <div class="row">
      <div class="col-xs-12">
          <p class="pull-right"><b>@lang('messages.date'):</b> {{ @format_date($new_orders[0]['created_at']) }}</p>
      </div>
    </div>
    <div class="row">
      <div class="col-sm-4">
        <b>{{ __('delivery.user_name') }}:</b>  {{ $new_orders[0]['user_name'] }}<br>
        <b>{{ __('delivery.user_phone') }}:</b> {{ $new_orders[0]['user_phone'] }}<br>
        <b>{{ __('delivery.user_email') }}:</b> {{ $new_orders[0]['user_email'] }}<br>
      </div>
      <div class="col-sm-4">
        <b>{{ __('delivery.area') }}:</b> {{ $new_orders[0]['order_area'] }}<br>
        <b>{{ __('delivery.area_address') }}:</b> {{ $new_orders[0]['order_area_address'] }}<br>
        <b>{{ __('delivery.payment_method') }}:</b> {{ $new_orders[0]['payment_method'] }}<br>
      </div>
    </div>
    <br>
    <div class="row">
      <div class="col-sm-12 col-xs-12">
        <h4>{{ __('delivery.products') }}:</h4>
      </div>

      <div class="col-sm-12 col-xs-12">
        <div class="table-responsive">
          <table class="table bg-gray">
            <tr class="bg-green">
              <th>#</th>
              <th>{{ __('delivery.product') }}</th>
              <th>{{ __('delivery.qty') }}</th>
              <th>{{ __('delivery.unit_price') }}</th>
              <th>{{ __('delivery.discount') }}</th>
              <th>{{ __('delivery.tax') }}</th>
              <th>{{ __('delivery.price_inc_tax') }}</th>
              <th>{{ __('delivery.subtotal') }}</th>
            </tr>
            @foreach($new_orders as $new_order)
              <tr>
                <td>{{ $loop->iteration }}</td>
                <td>
                  {{ $new_order['product_name'] }}
                </td>
                <td>{{ $new_order['product_quantity'] }}</td>
                <td>
                  <span class="display_currency" data-currency_symbol="true">{{ $new_order['unit_price'] }}</span>
                </td>
                <td>
                  <span class="display_currency" data-currency_symbol="true">{{ $new_order['discount'] }}</span>
                </td>
                <td>
                  <span class="display_currency" data-currency_symbol="true">{{ $new_order['tax'] }}</span> 
                </td>
                <td>
                  <span class="display_currency" data-currency_symbol="true">{{ $new_order['unit_price_inc_tax'] }}</span>
                </td>
                <td>
                  <span class="display_currency" data-currency_symbol="true">{{ $new_order['sub_total'] }}</span>
                </td>
              </tr>
            @endforeach
          </table>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-sm-12 col-xs-12">
        <h4>{{ __('delivery.delivery_info') }}:</h4>
        <div class="table-responsive">
          <table class="table bg-gray">
            <tr>
              <th>{{ __('delivery.total') }}: </th>
              <td></td>
              <td><span class="display_currency pull-right" data-currency_symbol="true">{{ $total_price }}</span></td>
            </tr>
            <tr>
              <th>{{ __('delivery.discount') }}:</th>
              <td><b>(-)</b></td>
              <td><span class="pull-right">{{ $total_discount }}</span></td>
            </tr>
            <tr>
              <th>{{ __('delivery.tax') }}:</th>
              <td><b>(+)</b></td>
              <td class="text-right"><span class="display_currency pull-right" data-currency_symbol="true">{{ $total_tax }}</span></td>
            </tr>
            <tr>
              <th>{{ __('delivery.delivery') }}</th>
              <td><b>(+)</b></td>
              <td><span class="display_currency pull-right" data-currency_symbol="true">{{ $delivery_price }}</span></td>
            </tr>
            <tr>
              <th>{{ __('delivery.total_bullet') }}</th>
              <td><b>(-)</b></td>
              <td><span class="display_currency pull-right" data-currency_symbol="true">{{ $new_orders[0]['points'] }}</span></td>
            </tr>
            <tr>
              <th>{{ __('delivery.total_payable') }}: </th>
              <td></td>
              <td><span class="display_currency pull-right">{{ $total_payable }}</span></td>
            </tr>
          </table>
        </div>
      </div>
    </div>
  </div>
  <div class="modal-footer">
      <button type="button" class="btn btn-default no-print" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>
  </div>
</div>

<script type="text/javascript">
  $(document).ready(function(){
    var element = $('div.modal-xl');
    __currency_convert_recursively(element);
  });
</script>
