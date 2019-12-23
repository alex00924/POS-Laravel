function pos_product_in_cart_row(variation_id, uid, product_quantity, selling_group_id){

    //Get item addition method
    var item_addtn_method = 0;
    var add_via_ajax = true;

    if($('#item_addition_method').length){
        item_addtn_method = $('#item_addition_method').val();
    }

    if(item_addtn_method == 0){
        add_via_ajax = true;
    } else {

        var is_added = false;

        //Search for variation id in each row of pos table
        $('#pos_table tbody').find('tr').each( function(){

            var row_v_id = $(this).find('.row_variation_id').val();
            var enable_sr_no = $(this).find('.enable_sr_no').val();
            var modifiers_exist = false;
            if($(this).find('input.modifiers_exist').length > 0){
                modifiers_exist = true;
            }

            if(row_v_id == variation_id && enable_sr_no !== '1' && !modifiers_exist && !is_added){
                add_via_ajax = false;
                is_added = true;

                //Increment product quantity
                qty_element = $(this).find('.pos_quantity');
                var qty = __read_number(qty_element);
                __write_number(qty_element, qty + 1);
                qty_element.change();

                round_row_to_iraqi_dinnar($(this));

                $('input#search_product').focus().select();
            }
        });
    }

    if(add_via_ajax){

        var product_row = $('input#product_row_count').val();
        var location_id = $('input#location_id').val();
        var customer_id = $('select#customer_id').val();
        var is_direct_sell = false;
        if($('input[name="is_direct_sale"]').length > 0 && $('input[name="is_direct_sale"]').val() == 1){
            is_direct_sell = true;
        }

        var price_group = selling_group_id;
        // if($('#price_group').length > 0){
        //     price_group = $('#price_group').val();
        // }

        $.ajax({
            method: "GET",
            url: "/sells/pos/get_product_in_cart_row/" + variation_id + '/' + location_id + '/' + product_quantity,
            async: false,
            data: {
                product_row: product_row,
                customer_id: customer_id,
                is_direct_sell: is_direct_sell,
                price_group: price_group,
                uid: uid
            },
            dataType: "json",
            success: function(result){
                if(result.success){
                    $('table#pos_table tbody').append(result.html_content).find('input.pos_quantity');
                    //set hidden value for uid
                    $('#uid').val(result.uid);
                    //increment row count
                    $('input#product_row_count').val(parseInt(product_row) + 1);
                    var this_row = $('table#pos_table tbody').find("tr").last();
                    pos_each_row(this_row);
                    pos_total_row();
                    if(result.enable_sr_no == '1'){
                        var new_row = $('table#pos_table tbody').find("tr").last();
                        new_row.find('.add-pos-row-description').trigger('click');
                    }

                    round_row_to_iraqi_dinnar(this_row);
                    __currency_convert_recursively(this_row);

                    $('input#search_product').focus().select();

                    //Used in restaurant module
                    if(result.html_modifier){
                        $('table#pos_table tbody').find("tr").last().find("td:first").append(result.html_modifier);
                    }

                } else {
                    swal(result.msg).then((value) => {
                        $('input#search_product').focus().select();
                    });
                }
            }
        });
    }
}

function get_recent_mobile_transaction() {
    let invoice_no = $('#mobile_invoice_no').val();
    let element_obj = $('div#mobile_transactions');
    $.ajax({
        method: "GET",
        url: "/sells/pos/get-recent-mobile-transactions",
        data: {invoice_no: invoice_no},
        dataType: "html",
        success: function(result){
            $('#mobile_transaction_box_btn').click();
            element_obj.html(result);
            __currency_convert_recursively(element_obj);
        }
    });
}

$(document).on('click', '.delete-mobile-sale', function(e){
    e.preventDefault();

});

//Finalize without showing payment options - pay by card machine
$('button.pos-card-machine').click(function(){
    //Check if product is present or not.
    if($('table#pos_table tbody').find('.product_row').length <= 0){
        toastr.warning(LANG.no_products_added);
        return false;
    }

    var pay_method = $(this).data('pay_method');

    //Check for remaining balance & add it in 1st payment row
    var total_payable = __read_number($('input#final_total_input'));
    var total_paying = __read_number($('input#total_paying_input'));
    if(total_payable > total_paying){

        var bal_due = total_payable - total_paying;

        var first_row = $('#payment_rows_div').find('.payment-amount').first();
        var first_row_val = __read_number(first_row);
        first_row_val = first_row_val + bal_due;
        __write_number(first_row, first_row_val);
        first_row.trigger('change');
    }

    if(pay_method === 'card_machine'){
        $('div#card_machine_modal').modal('show');
    }
});

//when click finalize button of card machine modal
$('button#pos-save-card-machine').click(function(){
    let receipt_no = $('#receipt_number').val();
    if(receipt_no === "") {
        toastr.warning("Please input receipt number.");

    } else {
        var pay_method = $('button.pos-card-machine').data('pay_method');
        //Change payment method.
        $('#receipt_no').val(receipt_no);

        $('#payment_rows_div').find('.payment_types_dropdown').first().val(pay_method);
        $('#receipt_number').val("");
        console.log("form", pos_form_obj);
        pos_form_obj.submit();
        $('div#card_machine_modal').modal('hide');
    }
});