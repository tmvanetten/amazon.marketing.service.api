<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\RequestApiReportsHelper;

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
        $executeRequest = new RequestApiReportsHelper();
        $result = $executeRequest->requestAmazonReports();
        if ($result['status']) {
            $this->info($result['quantity'] . ' report created & saved to database Date:' . date("Ymd h:i:s A"));
        } else {
            $this->error($result['message']);
        }
    }
}
