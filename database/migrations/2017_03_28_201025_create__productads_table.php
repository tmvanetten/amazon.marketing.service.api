<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProductadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('productads', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->unsignedBigInteger('campaign_id');
            $table->unsignedBigInteger('ad_group_id');
            $table->string('sku');
            $table->string('state');
            $table->timestamps();

            $table->primary('id');
            $table->foreign('campaign_id')->references('id')->on('campaigns');
            $table->foreign('ad_group_id')->references('id')->on('adgroups');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('productads');
    }
}
