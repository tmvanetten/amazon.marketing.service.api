<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStrategyHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('strategy_history', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->unsignedBigInteger('ad_group_id')->nullable();
            $table->unsignedBigInteger('keyword_id')->nullable();
            $table->unsignedInteger('updated_by');

            $table->string('message');
            $table->timestamps();

            $table->foreign('campaign_id')->references('id')->on('campaigns');
            $table->foreign('ad_group_id')->references('id')->on('adgroups');
            $table->foreign('keyword_id')->references('id')->on('keywords');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('strategy_history', function (Blueprint $table) {
            //
        });
    }
}
