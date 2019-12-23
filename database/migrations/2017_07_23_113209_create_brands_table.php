<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBrandsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id')->unsigned();
	        $table->string('name');
	        $table->text('description')->nullable();
	        $table->integer('created_by')->unsigned();

	        $table->softDeletes();
	        $table->timestamps();
        });

	    Schema::table('brands', function($table) {
		    $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
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
        Schema::dropIfExists('brands');
    }
}
