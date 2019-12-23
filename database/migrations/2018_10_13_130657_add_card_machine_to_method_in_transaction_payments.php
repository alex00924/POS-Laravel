<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddCardMachineToMethodInTransactionPayments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
	    DB::statement("ALTER TABLE transaction_payments CHANGE COLUMN method method ENUM('cash','card','cheque','bank_transfer','custom_pay_1','custom_pay_2','custom_pay_3','mobile','card_machine') NOT NULL DEFAULT 'cash'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
