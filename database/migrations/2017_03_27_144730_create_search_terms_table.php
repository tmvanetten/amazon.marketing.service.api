<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSearchTermsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('search_terms_report', function (Blueprint $table) {
            $table->increments('id');
            $table->string('campaign_name');
            $table->string('adgroup_name');
            $table->string('customer_search_term');
            $table->string('keyworod');
            $table->string('match_type');
            $table->unsignedInteger('impressions');
            $table->unsignedInteger('clicks');
            $table->decimal('ctr', 13, 2);
            $table->decimal('spend', 13, 2);
            $table->decimal('cpc', 13, 2);
            $table->decimal('acos', 13, 2);
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
        Schema::drop('search_terms_report');
    }
}
