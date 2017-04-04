<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\AutoCampaign;
use App\Model\Strategy;

class runStrategy extends Command {
    protected $strategy;
    protected $bidCollection;
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
        $this->strategy = Strategy::first();
        $this->bidCollection = Strategy::getBiddableAutoCampaign($recentBeginDate, $pastEndDate, $endDate);
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

    }

    /**
     * @return $this
     */
    public function changeBidding(){
        $this->autoGenerateAllData();
        /** @var $bidStrategyModel A4C_GoogleAdwords_Model_Strategy_Bid_Strategy */
        $bidStrategyModel = Mage::getSingleton('googleadwords/strategy_bid_strategy');
        $collection = $this->_adUnits;
        $bidCollection = array();

        /** @var $dateHelper A4C_GoogleAdwords_Helper_Date */
        $dateHelper = Mage::helper('googleadwords/date');
        $expiraryTimeStamp = $dateHelper->getCurrentTimeStamp() - 30 * 60;

        foreach ($collection as $key => $value) {
            $newCPC = $value['thisweekmax_cpc'];
            if( $this->_isEnabled($value) ) {
                $strategy = $this->_getStrategySettingByProductData($value);
                $newCPCArray = $bidStrategyModel->CPCCalculation($value, $strategy, $newCPC);
                $newCPC = $newCPCArray['max_cpc'];
                $updatedBy = $newCPCArray['updated_by'] > 0 ? $this->_strategyLabel . " #" . $newCPCArray['updated_by'] : null;
            }
            //insert to bid collection if max cpc changed.
            if( $value['thisweekmax_cpc'] != $newCPC ) {
                $expiraryTime = $this->_getExpiraryTime($value, $expiraryTimeStamp);
                $newCPC = round($newCPC, -4);
                $bidCollection[] = array(
                    'id' => $value['id'],
                    'criterion_id' => $value['criterion_id'],
                    'ad_group_id' => $value['ad_group_id'],
                    'old_cpc' => $value['thisweekmax_cpc'],
                    'max_cpc' => $newCPC,
                    'updated_by' => $updatedBy,
                    'expirary_time' => $expiraryTime
                );
            }
        }

        $this->automatedBidding($bidCollection);
        return $this;
    }
}
