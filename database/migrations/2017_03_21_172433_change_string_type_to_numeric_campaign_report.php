<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeStringTypeToNumericCampaignReport extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('campaign_report', function (Blueprint $table) {
            $table->decimal('dailyBudget', 8, 2)->change();
            $table->decimal('cost', 8, 2)->change();
            $table->decimal('attributedSales1d', 8, 2)->change();
            $table->decimal('attributedSales1dSameSKU', 8, 2)->change();
            $table->integer('impressions')->change();
            $table->integer('clicks')->change();
            $table->integer('attributedConversions1dSameSKU')->change();
            $table->integer('attributedConversions1d')->change();
        });

        Schema::table('ad_group_report', function (Blueprint $table) {
            $table->decimal('defaultBid', 8, 2)->change();
            $table->decimal('cost', 8, 2)->change();
            $table->decimal('attributedSales1d', 8, 2)->change();
            $table->decimal('attributedSales1dSameSKU', 8, 2)->change();
            $table->integer('impressions')->change();
            $table->integer('clicks')->change();
            $table->integer('attributedConversions1dSameSKU')->change();
            $table->integer('attributedConversions1d')->change();
        });

        Schema::table('keywords_report', function (Blueprint $table) {
            $table->decimal('bid', 8, 2)->change();
            $table->decimal('cost', 8, 2)->change();
            $table->decimal('attributedSales1d', 8, 2)->change();
            $table->decimal('attributedSales1dSameSKU', 8, 2)->change();
            $table->integer('impressions')->change();
            $table->integer('clicks')->change();
            $table->integer('attributedConversions1dSameSKU')->change();
            $table->integer('attributedConversions1d')->change();
        });

        Schema::table('product_ads_report', function (Blueprint $table) {
            $table->decimal('cost', 8, 2)->change();
            $table->decimal('attributedSales1d', 8, 2)->change();
            $table->decimal('attributedSales1dSameSKU', 8, 2)->change();
            $table->integer('impressions')->change();
            $table->integer('clicks')->change();
            $table->integer('attributedConversions1dSameSKU')->change();
            $table->integer('attributedConversions1d')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
