<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRequestReportApiTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reqest_report_api', function (Blueprint $table) {
            $table->increments('id');
            $table->string('type');
            $table->dateTime('report_date');
            $table->string('amazn_profile_id');
            $table->string('amazn_report_id');
            $table->string('amazn_report_date');
            $table->string('amazn_record_type');
            $table->string('amazn_status');
            $table->text('amazn_status_details');
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
       Schema::dropIfExists('reqest_report_api');
    }
}
