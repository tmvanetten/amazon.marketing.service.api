<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReportItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('report_items', function (Blueprint $table) {
            $table->increments('id');
            $table->string('amazn_profile_id');
            $table->string('amazn_report_id');
            $table->string('adId')->nullable();
            $table->string('clicks')->nullable();
            $table->string('cost')->nullable();
            $table->string('impressions')->nullable();
            $table->string('attributedConversions1dSameSKU')->nullable();
            $table->string('attributedSales7d')->nullable();
            $table->string('attributedSales30d')->nullable();
            $table->string('attributedSales1d')->nullable();
            $table->string('attributedConversions1d')->nullable();
            $table->string('attributedSales7dSameSKU')->nullable();
            $table->string('attributedSales1dSameSKU')->nullable();
            $table->string('attributedSales30dSameSKU')->nullable();
            $table->string('attributedConversions30d')->nullable();
            $table->string('attributedConversions7d')->nullable();
            $table->string('attributedConversions30dSameSKU')->nullable();
            $table->string('attributedConversions7dSameSKU')->nullable();
            $table->timestamps();
            $table->integer('request_report_id')->unsigned();
            $table->foreign('request_report_id')
                ->references('id')->on('reqest_report_api')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('report_items');
    }
}
