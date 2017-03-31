<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSearchTermHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('search_term_history', function (Blueprint $table) {
            $table->increments('id');
            $table->string('search_term');
            $table->string('message');
            $table->string('type');
            $table->string('match_type');
            $table->unsignedBigInteger('campaign_id');
            $table->unsignedBigInteger('ad_group_id');
            $table->timestamps();

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
        Schema::drop('search_term_history');
    }
}
