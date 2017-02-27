<?php
namespace App\Console\Commands;

require_once base_path('vendor/amzn/amazon-advertising-api-php/AmazonAdvertisingApi/Client.php');

use Illuminate\Console\Command;
use AmazonAdvertisingApi\Client;

class AmazonDownload extends Command
{
    const CLIENT_ID = "amzn1.application-oa2-client.cdb160f5ae1c4d1aa568aff3fa01a5c4";
    const CLIENT_SECRET = "a414d59747a82867e9f9082f04a68f4d2320d2f680d947f381d319e918ffcba6";
    const REFRESH_TOKEN = "Atzr|IwEBIPpTfpWlipmmUnA04wGU9_WUN4JGdk1s-ysgozW9A7GYoWHAY2cCLK71c2o4jvyEW4Y9nWFZkZ5x7e3T3Zh6C1r2ypJYEuZ89rQC09wxC0_7U_HvmsCfJe0GaROzzDGKQuvwHkVY1CX3pPxccniOcD9DpV3qrzyI_YiJ7dxIq3zaoqN1LqETCif7fRrCUb2E5Y9vVEjEs8U4vpm_di9-rG3v5V526sGziHirrlvq8PpHpj5VqFSxM-P0jUNGYrYrVrXyaxgyhxmNmg8mhHnn1h3_ilj4-DKTHQeeF4KYpw2OjKGOj3UdY8JSE33vvlBLrU_HX2U9qp63FCXNq6yousOz7bksP6pPPs697oin5pnPGzGphTO7z4KyqRQwFYas8BCXTh8UVOyeUenbb4jD__EmpiG_cbyutQNQdy3axnL3OPRcl-AKAB8xNa1jNMywvXlH53pyf1TE53bQFY7ADZnpQh9yyfmHTySPn5twI1PkeRHQRoyJDH4MOQycCR0Ou-xsRDaEpVqc43nDO4bbza-a6m6OPYM01CwZmiVO5Ej9PGhm39vxPuMiechM0u6smgQ";

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'amazon:download';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'download amazon report';

    /**
     * Amazon api login config
     *
     * @var array
     */
    protected $config = [];

    /**
     * Amazon Cilent
     *
     * @var AmazonAdvertisingApi\Client
     */
    protected $cilent;

    /**
     * Create a new command instance.
     *
     * @return void
     */

    public function __construct()
    {
        $this->config = [
            "clientId" => SELF::CLIENT_ID,
            "clientSecret" => SELF::CLIENT_SECRET,
            "refreshToken" => SELF::REFRESH_TOKEN,
            "region" => "na",
            "sandbox" => false,
        ];
        $this->cilent = new Client($this->config);
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $request = $this->cilent->getProfiles();
        var_dump($request);
    }
}
