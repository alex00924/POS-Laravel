<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddReceiptNoUidColumnToTransactionPayments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
	    Schema::table('transaction_payments', function(Blueprint $table) {
		    $table->string('receipt_no')->after('payment_ref_no')->default("");
		    $table->string('uid')->after('receipt_no')->default("");
	    });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
	    Schema::dropIfExists('transaction_payments');
	    Schema::dropIfExists('uid');
    }
}
