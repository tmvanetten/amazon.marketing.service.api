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
        $this->info(' ');
        $this->info('--- Start Command ---');
        $this->info(' ');
        $executeRequest = new RequestApiReportsHelper();
        $result = $executeRequest->requestAmazonReports();
        if ($result['status']) {
            $this->info(' ');
            $this->info($result['quantity'] . ' report created & saved to database ');
            $this->info(' ');
        } else {
            $this->info(' ');
            $this->error($result['message']);
            $this->info(' ');
        }
        $this->info(' ');
        $this->info('--- Finish Command ---');
    }
}
