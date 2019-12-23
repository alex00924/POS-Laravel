<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMobileCardTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mobile_card_transactions', function (Blueprint $table) {
	        $table->increments('id');
	        $table->integer('business_id')->unsigned();
	        $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
	        $table->integer('location_id')->unsigned();
	        $table->foreign('location_id')->references('id')->on('business_locations')->onDelete('cascade');
	        $table->enum('type', ['purchase', 'sell']);
	        $table->enum('status', ['received', 'pending', 'ordered', 'draft', 'final']);
	        $table->enum('payment_status', ['paid', 'due']);
	        $table->string('invoice_no')->nullable();
	        $table->dateTime('transaction_date');
	        $table->decimal('total_before_tax', 8, 2)->default(0)->comment('Total before the purchase/invoice tax, this includeds the indivisual product tax');
	        $table->decimal('tax_amount', 8, 2)->default(0);
	        $table->decimal('final_total', 8, 2)->default(0);
	        $table->string('created_by');
	        $table->timestamps();

	        //Indexing
	        $table->index('business_id');
	        $table->index('type');
	        $table->index('transaction_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mobile_card_transactions');
    }
}
