<?php

namespace App\Console\Commands;

require_once base_path('vendor/amzn/amazon-advertising-api-php/AmazonAdvertisingApi/Client.php');

use Illuminate\Console\Command;
use AmazonAdvertisingApi\Client;
use App\RequestReportAPI;

class RequestApiReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'amazon:request_report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Used to request report via Amazon API & Saved to database';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info(' ');
        $this->info('--- Start Command ---');
        $this->info(' ');
        $dates = [
            'yesterday' => date("Ymd", time() - 60 * 60 * 24),
            'pastTwoDays' => date("Ymd", time() - 60 * 60 * 48),
            'pastThreeDays' => date("Ymd", time() - 60 * 60 * 72),
            'pastFourDays' => date("Ymd", time() - 60 * 60 * 96),
            'pastFiveDays' => date("Ymd", time() - 60 * 60 * 120),
            'pastSixDays' => date("Ymd", time() - 60 * 60 * 144),
            'pastSevenDays' => date("Ymd", time() - 60 * 60 * 168)
        ];

        $today = date("Ymd", time() - 60 * 60 * 24);
        $config = array(
            "clientId" => env('AMAZN_ID'),
            "clientSecret" => env('AMAZN_SECRET'),
            "refreshToken" => env('AMAZN_TOKEN'),
            "region" => env('AMAZN_REGION'),
            "sandbox" => env('AMAZN_SANDBOX'),
        );

        $client = new Client($config);


        $request = $client->getProfiles();
        if ($request['success'] != 1) {
            $this->error('Error: Not any profile found.');
            exit;
        }
        $data = $request['response'];
        //var_dump($request);die;
        $profileData = json_decode($data);
        $successReportCreated = 0;
        foreach($profileData as $profile) {
            $client->profileId = $profile->profileId;
            var_dump($profile->profileId);
            $reportTypes = array(
                'campaigns',
                'adGroups',
                'keywords',
                'productAds',
                );

            foreach($reportTypes as $reportType) {
                $reportAlreadyExists = RequestReportAPI::where('amazn_profile_id', $profile->profileId)
                    ->where('type', $reportType)
                    ->orderBy('amazn_report_date', 'desc')
                    ->first();
                if(!is_null($reportAlreadyExists) && $reportAlreadyExists->amazn_report_date < $dates['pastSevenDays']){
                    $request = $client->requestReport($reportType, array(
                        'campaignType' => 'sponsoredProducts',
                        'reportDate' => $dates['yesterday'],
                        'metrics' => 'impressions,clicks,cost,attributedConversions1dSameSKU,attributedConversions1d,attributedSales1dSameSKU,attributedSales1d'));
                    $data = $request['response'];
                    $report = json_decode($data);
                    if (!empty($report->reportId)) {
                        $item = new RequestReportAPI;
                        $item->type = $reportType;
                        $item->report_date = date('Y-m-d');
                        $item->amazn_profile_id = $client->profileId;
                        $item->amazn_report_id = $report->reportId;
                        $item->amazn_report_date = $dates['yesterday'];
                        $item->amazn_record_type = $report->recordType;
                        $item->amazn_status = $report->status;
                        $item->amazn_status_details = $report->statusDetails;
                        $item->save();
                        $reportAlreadyExists->delete();
                        $successReportCreated++;
                    }  else {
                        $this->error('Error: Not generate report request. Amazon error: '.$report->details);
                    }
                }else{
                    RequestReportAPI::where('type', $reportType)->where('amazn_profile_id', $client->profileId)->delete();
                    foreach($dates as $date){
                        $request = $client->requestReport($reportType, array(
                            'campaignType' => 'sponsoredProducts',
                            'reportDate' => $date,
                            'metrics' => 'impressions,clicks,cost,attributedConversions1dSameSKU,attributedConversions1d,attributedSales1dSameSKU,attributedSales1d'));
                        $data = $request['response'];
                        $report = json_decode($data);
                        if (!empty($report->reportId)) {
                            $item = new RequestReportAPI;
                            $item->type = $reportType;
                            $item->report_date = date('Y-m-d');
                            $item->amazn_profile_id = $client->profileId;
                            $item->amazn_report_id = $report->reportId;
                            $item->amazn_report_date = $date;
                            $item->amazn_record_type = $report->recordType;
                            $item->amazn_status = $report->status;
                            $item->amazn_status_details = $report->statusDetails;
                            $item->save();
                            $successReportCreated++;
                        }  else {
                            $this->error('Error: Not generate report request. Amazon error: '.$report->details);
                        }
                    }
                }
            }            
        }
        if ($successReportCreated) {
            $this->info(' ');
            $this->info($successReportCreated . ' report created & saved to database ');
            $this->info(' ');
        } else {
            $this->info(' ');
            $this->info('All Report for today was already created & saved to database.');
            $this->info(' ');
        }
        $this->info(' ');
        $this->info('--- Finish Command ---');
    }
}
