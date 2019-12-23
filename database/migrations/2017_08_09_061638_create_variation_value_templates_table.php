<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVariationValueTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('variation_value_templates', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('variation_template_id')->unsigned();

            $table->timestamps();

            //Indexing
            $table->index('name');
            $table->index('variation_template_id');
        });
	    Schema::table('variation_value_templates', function($table) {
		    $table->foreign('variation_template_id')->references('id')->on('variation_templates')->onDelete('cascade');
	    });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('variation_value_templates');
    }
}
