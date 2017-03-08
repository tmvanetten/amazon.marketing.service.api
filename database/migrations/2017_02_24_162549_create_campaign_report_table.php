<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCampaignReportTable extends Migration
{
    public function up()
    {
        Schema::create('campaign_report', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('request_report_id')->nullable();
            $table->foreign('request_report_id')
                ->references('id')->on('reqest_report_api')->onDelete('cascade')->onUpdate('cascade');
            $table->bigInteger('campaignId')->nullable();
            $table->string('name')->nullable();
            $table->string('campaignType')->nullable();
            $table->string('targetingType')->nullable();
            $table->string('premiumBidAdjustment')->nullable();
            $table->string('dailyBudget')->nullable();
            $table->string('startDate')->nullable();
            $table->string('state')->nullable();
            $table->string('clicks')->nullable();
            $table->string('cost')->nullable();
            $table->string('impressions')->nullable();
            $table->string('attributedConversions1dSameSKU')->nullable();
            $table->string('attributedSales1d')->nullable();
            $table->string('attributedConversions1d')->nullable();
            $table->string('attributedSales1dSameSKU')->nullable();
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
        Schema::dropIfExists('campaign_report');
    }
}
