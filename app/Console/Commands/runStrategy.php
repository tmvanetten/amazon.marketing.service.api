<?php

namespace App\Console\Commands;

use App\Model\Campaigns;
use Illuminate\Console\Command;
use App\Helpers\CampaignStrategy;
use App\Helpers\AmazonAPI;
use App\Helpers\AmazonAPIException;
use App\Model\Strategy;
use App\Model\StrategyHistory;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use \Exception;

class runStrategy extends Command {
    const BID_TYPE_ADGROUP = 'adgroup';
    const BID_TYPE_KEYWORD = 'keyword';

    protected $strategyModel;
    protected $strategy;
    protected $bidAutoCollection;
    protected $bidManualCollection;
    protected $amazonClient;
    protected $campaignStrategyHelper;
    protected $log;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'strategy:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'run automatic bidding base on strategy setting';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        // create a log channel
        $log = new Logger('Amazon bidding log');
        $log->pushHandler(new StreamHandler('storage/logs/amazon_bidding.log'));
        $this->log = $log;
        // prepare strategy model
        $stratgyModel = Strategy::first();
        $this->strategyModel = $stratgyModel;
        $this->bidAutoCollection = [];
        $this->bidManualCollection = [];
        if($this->strategyModel) {
            //prepare amazon client
            $this->amazonClient = new AmazonAPI();
            //extract strategy setting from strategy model
            $this->strategy = json_decode($stratgyModel->strategy, true);
            //prepare strategy calculation helper
            $this->campaignStrategyHelper = new CampaignStrategy($this->strategy, $this->strategyModel);
            //extract date settings
            $dateOffset = $stratgyModel->date_offset;
            $recentDayRange = $stratgyModel->recent_date_range;
            $recentDays = $dateOffset + $recentDayRange - 1;
            $pastDayRange = $stratgyModel->past_date_range;
            $pastDays = $dateOffset + $pastDayRange - 1;
            $rangeDays = $stratgyModel->run_days;
            $endDate = date("Y-m-d", strtotime("-$dateOffset days"));
            $recentBeginDate = date("Y-m-d", strtotime("-$recentDays days"));
            $pastBeginDate = date("Y-m-d", strtotime("-$pastDays days"));
            $runDate = date("Y-m-d", strtotime("-$rangeDays days"));

            //prepare bid collection for automatic campaign
            $recentData  = Campaigns::getBiddableAutoCampaign($recentBeginDate, $endDate, $runDate);
            $pastData  = Campaigns::getBiddableAutoCampaign($pastBeginDate, $endDate, $runDate);
            $bidCollection = [];
            foreach($recentData as $adgroupId => $data) {
                if(isset($pastData[$adgroupId])) {
                    $bidCollection[] = [
                        'adGroupId' => $data->adGroupId,
                        'campaignId' => $data->campaignId,
                        'defaultBid' => $data->defaultBid,
                        'click_recent' => $data->clicks,
                        'cost_recent' => $data->cost,
                        'impressions_recent' => $data->impressions,
                        'sales_recent' => $data->sales,
                        'conversions_recent' => $data->conversions,
                        'cpc_recent' => $data->cpc,
                        'acos_recent' => $data->acos,
                        'click_past' => $pastData[$adgroupId]->clicks,
                        'cost_past' => $pastData[$adgroupId]->cost,
                        'impressions_past' => $pastData[$adgroupId]->impressions,
                        'sales_past' => $pastData[$adgroupId]->sales,
                        'conversions_past' => $pastData[$adgroupId]->conversions,
                        'cpc_past' => $pastData[$adgroupId]->cpc,
                        'acos_past' => $pastData[$adgroupId]->acos,
                    ];
                }
            }
            $this->bidAutoCollection = $bidCollection;

            //prepare bid collection for manual campaign
            $recentData  = Campaigns::getBiddableManualCampaign($recentBeginDate, $endDate, $runDate);
            $pastData  = Campaigns::getBiddableManualCampaign($pastBeginDate, $endDate, $runDate);
            $bidCollection = [];
            foreach($recentData as $adgroupId => $data) {
                if(isset($pastData[$adgroupId])) {
                    $bidCollection[] = [
                        'keywordId' => $data->keywordId,
                        'adGroupId' => $data->adGroupId,
                        'campaignId' => $data->campaignId,
                        'defaultBid' => $data->defaultBid,
                        'click_recent' => $data->clicks,
                        'cost_recent' => $data->cost,
                        'impressions_recent' => $data->impressions,
                        'sales_recent' => $data->sales,
                        'conversions_recent' => $data->conversions,
                        'cpc_recent' => $data->cpc,
                        'acos_recent' => $data->acos,
                        'click_past' => $pastData[$adgroupId]->clicks,
                        'cost_past' => $pastData[$adgroupId]->cost,
                        'impressions_past' => $pastData[$adgroupId]->impressions,
                        'sales_past' => $pastData[$adgroupId]->sales,
                        'conversions_past' => $pastData[$adgroupId]->conversions,
                        'cpc_past' => $pastData[$adgroupId]->cpc,
                        'acos_past' => $pastData[$adgroupId]->acos,
                    ];
                }
            }
            $this->bidManualCollection = $bidCollection;
        }
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if($this->strategy) {
            try{
                $this->changeAutoCamapaignBidding();
                $this->changeManualCampaignBidding();
                $this->strategyModel->last_run = date("Y-m-d");
                $this->strategyModel->save();
            } catch( Exception $e ) {
                $this->log->error($e->getMessage());
            }
        }

    }

    /**
     * @return $this
     */
    public function changeAutoCamapaignBidding(){
        $bidCollection = [];
        $collection = $this->bidAutoCollection;
        $autoCampaignHelper = $this->campaignStrategyHelper;
        if(count($collection)) {
            foreach ($collection as $item) {
                $currentBid = $item['defaultBid'];
                $newBidArray = $autoCampaignHelper->CPCCalculation($item);
                //insert to bid collection if max cpc changed.
                if( $newBidArray['bid'] != $currentBid ) {
                    $bidCollection[] = [
                        'campaign_id' => $item['campaignId'],
                        'ad_group_id' => $item['adGroupId'],
                        'old_bid' => $currentBid,
                        'new_bid' => $newBidArray['bid'],
                        'updated_by' => $newBidArray['updated_by']
                    ];
                }
            }
        }
        $this->automatedBidding($bidCollection, runStrategy::BID_TYPE_ADGROUP);
        return $this;
    }

    /**
     * @return $this
     */
    public function changeManualCampaignBidding(){
        $bidCollection = [];
        $collection = $this->bidManualCollection;
        $autoCampaignHelper = $this->campaignStrategyHelper;
        if(count($collection)) {
            foreach ($collection as $item) {
                $currentBid = $item['defaultBid'];
                $newBidArray = $autoCampaignHelper->CPCCalculation($item);
                //insert to bid collection if max cpc changed.
                if( $newBidArray['bid'] != $currentBid ) {
                    $bidCollection[] = [
                        'keyword_id' => $item['keywordId'],
                        'campaign_id' => $item['campaignId'],
                        'ad_group_id' => $item['adGroupId'],
                        'old_bid' => $currentBid,
                        'new_bid' => $newBidArray['bid'],
                        'updated_by' => $newBidArray['updated_by']
                    ];
                }
            }
        }
        $this->automatedBidding($bidCollection, runStrategy::BID_TYPE_KEYWORD);
        return $this;
    }

    protected function automatedBidding( $bidCollection, $type ) {
        foreach( $bidCollection as $bid) {
            try{
                switch($type) {
                    case runStrategy::BID_TYPE_ADGROUP:
                        $this->amazonClient->bidOnAdgroup($bid['ad_group_id'], $bid['new_bid']);
                        break;
                    case runStrategy::BID_TYPE_KEYWORD:
                        $this->amazonClient->bidOnKeyword($bid['keyword_id'], $bid['new_bid']);
                        break;
                    default:
                        throw new Exception('Incorrect or no type defined for bidding!');
                        break;
                }
                StrategyHistory::create($bid);
            } catch (AmazonAPIException $e) {
                $this->log->error($e->getMessage());
            }
        }
        return $this;
    }
}
