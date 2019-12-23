<?php
	/**
	 * Created by PhpStorm.
	 * User: User
	 * Date: 10/5/2018
	 * Time: 8:42 PM
	 */
	?>
@if(!empty($transactions) && count($transactions) > 0)
    <table class="table table-slim no-border">
        <thead>
            <tr>
                <th>No.</th>
                <th>Invoice No</th>
                <th>Final Total</th>
                <th>Card Type</th>
                <th>Transaction Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($transactions as $transaction)
            <tr class="cursor-pointer view_mobile_modal" data-href="{{action('SellController@show', [$transaction->id])}}">
                <td>
                    {{ $loop->iteration}}.
                </td>
                <td>
                    {{ $transaction->invoice_no }}
                </td>
                <td class="display_currency">
                    {{ $transaction->final_total }}
                </td>
                <td>
                    {{ $transaction->card_type }}
                </td>
                <td>
                    {{ $transaction->transaction_date }}
                </td>
                <td>
                    <a href="{{action('SellPosController@destroy', [$transaction->id])}}" class="delete-mobile-sale" style="padding-left: 20px"><i class="fa fa-trash text-danger" title="{{__('lang_v1.click_to_delete')}}"></i></a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@else
    <p>@lang('sale.no_recent_transactions')</p>
@endif