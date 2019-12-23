<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductVariationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_variations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('product_id')->unsigned();

            $table->timestamps();

            //Indexing
            $table->index('name');
            $table->index('product_id');
        });
	    Schema::table('product_variations', function($table) {
		    $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
		    $table->boolean('is_dummy')->default(1);
	    });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_variations');
    }
}
