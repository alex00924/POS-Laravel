<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePurchaseLinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchase_lines', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('transaction_id')->unsigned();

            $table->integer('product_id')->unsigned();

            $table->integer('variation_id')->unsigned();

            $table->integer('quantity');
            $table->decimal('purchase_price', 8, 2);
            $table->decimal('purchase_price_inc_tax', 8, 2)->default(0);
            $table->decimal('item_tax', 8, 2)->comment("Tax for one quantity");
            $table->integer('tax_id')->nullable()->unsigned();

            $table->timestamps();
        });
	    Schema::table('purchase_lines', function($table) {
		    $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('cascade');
		    $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
		    $table->foreign('variation_id')->references('id')->on('variations')->onDelete('cascade');
		    $table->foreign('tax_id')->references('id')->on('tax_rates')->onDelete('cascade');
	    });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('purchase_lines');
    }
}
