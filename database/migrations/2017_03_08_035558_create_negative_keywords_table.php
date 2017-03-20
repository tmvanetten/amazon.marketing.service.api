<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNegativeKeywordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('negative_keywords', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('ad_group_id')->nullable();
            $table->foreign('ad_group_id')
                ->references('id')->on('ad_group_report')->onDelete('cascade')->onUpdate('cascade');
            $table->bigInteger('keywordId')->nullable();
            $table->bigInteger('campaignId')->nullable();
            $table->bigInteger('adGroupId')->nullable();
            $table->tinyInteger('enabled')->default(0);
            $table->string('keywordText')->nullable();
            $table->string('matchType')->nullable();
            $table->string('state')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('negative_keywords');
    }
}
