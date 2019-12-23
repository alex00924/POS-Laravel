<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('business_id')->unsigned();

            $table->enum('type', ['single', 'variable']);
            $table->integer('unit_id')->unsigned();

            $table->integer('brand_id')->nullable()->unsigned();

            $table->integer('category_id')->nullable()->unsigned();

            $table->integer('sub_category_id')->nullable()->unsigned();

            $table->integer('tax')->nullable()->unsigned();
            $table->foreign('tax')->references('id')->on('tax_rates');
            $table->enum('tax_type', ['inclusive', 'exclusive']);
            $table->boolean('enable_stock')->default(0);
            $table->integer('alert_quantity');
            $table->string('sku');
            $table->enum('barcode_type', ['C39', 'C128', 'EAN-13', 'EAN-8', 'UPC-A', 'UPC-E', 'ITF-14']);
            $table->integer('created_by')->unsigned();

            $table->timestamps();

            //Indexing
            $table->index('name');
            $table->index('business_id');
            $table->index('unit_id');
            $table->index('created_by');
        });
	    Schema::table('products', function($table) {
		    $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
		    $table->foreign('unit_id')->references('id')->on('units')->onDelete('cascade');
		    $table->foreign('brand_id')->references('id')->on('brands')->onDelete('cascade');
		    $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
		    $table->foreign('sub_category_id')->references('id')->on('categories')->onDelete('cascade');
		    $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
	    });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
}
