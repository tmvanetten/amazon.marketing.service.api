<?php
namespace App\Helpers;

use \Exception;

class CampaignStrategy {

    protected $strategies;
    protected $strategyModel;

    public function __construct($strategy, $strategyModel) {
        $this->strategies = $strategy;
        $this->strategyModel = $strategyModel;
    }

    /**
     * to calculate max cpc of a criterion base on custom strategy settings
     *
     * @param $value array criterion info
     * @return float
     */
    public function CPCCalculation($value) {
        $updatedBy = 0;
        $strategies = $this->strategies;
        $maxCPC = $this->_convertStringToFloat($value['defaultBid']);
        foreach($strategies as $index => $strategy) {
            $testResults = array();
            $applyStrategy = false;
            $conditions = $strategy['rules'];
            foreach( $conditions as $condition ) {
                $testResults[] = $this->_conditionTest($value, $condition);
            }
            $conditionsCriteria = $strategy['conditioncriteria'];
            switch( $conditionsCriteria ) {
                case 'all':
                    $decision = true;
                    foreach( $testResults as $testResult) {
                        if( $testResult == false ) {
                            $decision = false;
                            break;
                        }
                    }
                    $applyStrategy = $decision;
                    break;
                case 'any':
                    $decision = false;
                    foreach( $testResults as $testResult) {
                        if( $testResult == true ) {
                            $decision = true;
                            break;
                        }
                    }
                    $applyStrategy = $decision;
                    break;
            }
            if( $applyStrategy ) {
                if( isset($strategy['action']) && isset($strategy['action_percent']) ) {
                    $updatedBy = $index + 1;
                    $actionType = $strategy['action'];
                    $actionValue = $this->_convertPercentToFloat($strategy['action_percent']);
                    $offset = $this->_normailizeNumber($maxCPC * $actionValue);
                    switch($actionType) {
                        case 'increase':
                            $maxCPC = $maxCPC + $offset;
                            break;
                        case 'decrease':
                            $maxCPC = $maxCPC - $offset;
                            break;
                    }
                }
                break;
            }
        }
        return ["bid" => $maxCPC, "updated_by" => $updatedBy];
    }

    /**
     * process strategy base on its condition type
     * @param $value array criterion info
     * @param $condition array strategy condition info
     * @return bool
     */
    protected function _conditionTest( $value, $condition ) {
        return $this->_conditionTestTypeZero($value, $condition);
    }

    /**
     * raw comparison condition
     * one to one comparison
     * @param $value array criterion info
     * @param $condition array strategy condition info
     * @return bool
     */
    protected function _conditionTestTypeZero( $value, $condition )
    {
        //first entity value
        $InputEntityOne = $this->_getInputValue($value, $condition['input']);
        //comparison condition
        $conditionValue = $condition['condition'];
        //second entity value
        $compareValue   = $this->_getCompareValue($value, $condition);
        switch( $conditionValue ) {
            case 'equal':
                $result = $InputEntityOne == $compareValue;
                break;
            case 'greater':
                $result = $InputEntityOne > $compareValue;
                break;
            case 'equals_greater':
                $result = $InputEntityOne >= $compareValue;
                break;
            case 'less':
                $result = $InputEntityOne < $compareValue;
                break;
            case 'equal_less':
                $result = $InputEntityOne <= $compareValue;
                break;
            case 'not_equal':
                $result = $InputEntityOne != $compareValue;
                break;
            default:
                $result = false;
                break;
        }
        return $result;
    }

    /**
     * return compare to entity numeric value
     *
     * @param $value array criterion info
     * @param $condition array compare to entity info
     * @return float|int
     */
    protected function _getCompareValue( $value, $condition ) {
        $selectedCompareType = $condition['compare'];
        switch( $selectedCompareType ) {
            case "compare_to":
                $result = $this->_getInputValue($value, $condition['compare_to_input']);
                break;
            case "compare_exact":
                $inputValue = $condition['compare_to_exact'];
                $result = $this->_convertStringToFloat($inputValue);
                break;
            case "compare_to_percent":
                $percent = $this->_convertPercentToFloat($condition['compare_to_percent']);
                $inputValue = $this->_getInputValue($value, $condition['compare_to_input']);
                $result = $inputValue * $percent;
                break;
            default:
                $result = 0;
                break;
        }
        return $result;
    }

    /**
     * return input entity numeric value
     *
     * @param $value array criterion info
     * @param $field compare to entity name
     * @return float
     */
    protected function _getInputValue( $value, $field ) {
        $setting = $this->strategyModel;
        $recentRange = $this->_convertStringToFloat($setting->recent_date_range);
        $pastRange = $this->_convertStringToFloat($setting->past_date_range);
        switch( $field ) {
            case 'spend_recent':
                $result = $this->_convertStringToFloat($value['cost_recent']);
                break;
            case 'spend_past_eq':
                $pastTotal = $this->_convertStringToFloat($value['cost_past']);
                $result = $pastTotal * ($recentRange / $pastRange);
                break;
            case 'spend_past':
                $result = $this->_convertStringToFloat($value['cost_past']);
                break;
            case 'sales_recent':
                $result = $this->_convertStringToFloat($value['sales_recent']);
                break;
            case 'sales_past_eq':
                $pastTotal = $this->_convertStringToFloat($value['sales_past']);
                $result = $pastTotal * ($recentRange / $pastRange);
                break;
            case 'sales_past':
                $result = $this->_convertStringToFloat($value['sales_past']);
                break;
            case 'impression_recent':
                $result = $this->_convertStringToFloat($value['impressions_recent']);
                break;
            case 'impression_past_eq':
                $pastTotal = $this->_convertStringToFloat($value['impressions_past']);
                $result = $pastTotal * ($recentRange / $pastRange);
                break;
            case 'impression_past':
                $result = $this->_convertStringToFloat($value['impressions_past']);
                break;
            case 'click_recent':
                $result = $this->_convertStringToFloat($value['click_recent']);
                break;
            case 'click_past_eq':
                $pastTotal = $this->_convertStringToFloat($value['click_past']);
                $result = $pastTotal * ($recentRange / $pastRange);
                break;
            case 'click_past':
                $result = $this->_convertStringToFloat($value['click_past']);
                break;
            case 'cpc_recent':
                $result = $this->_convertStringToFloat($value['cpc_recent']);
                break;
            case 'cpc_past':
                $result = $this->_convertStringToFloat($value['cpc_past']);
                break;
            case 'acos_recent':
                $result = $this->_convertStringToFloat($value['acos_recent']);
                break;
            case 'acos_past':
                $result = $this->_convertStringToFloat($value['acos_past']);
                break;
            case 'diff_spend':
                $pastTotal = $this->_convertStringToFloat($value['cost_past']) * ($recentRange / $pastRange);
                $recentTotal = $this->_convertStringToFloat($value['cost_recent']);
                $result = $recentTotal - $pastTotal;
                break;
            case 'diff_sales':
                $pastTotal = $this->_convertStringToFloat($value['sales_past']) * ($recentRange / $pastRange);
                $recentTotal = $this->_convertStringToFloat($value['sales_recent']);
                $result = $recentTotal - $pastTotal;
                break;
            default:
                $result = 0;
                break;
        }
        return $result;
    }

    protected function _normailizeNumber($value) {
        if( !is_numeric($value) )
            $value = 0;
        $value = $this->_convertStringToFloat($value);
        $value = ceil($value * 100) / 100;
        return $value;
    }

    protected function _convertStringToFloat( $value ) {
        if( !is_numeric($value) )
            $value = 0;
        return (float) $value;
    }

    protected function _convertToPercent( $value ) {
        return  number_format( $value * 100, 2 );
    }

    protected function _convertPercentToFloat( $value ) {
        return (float) $value / 100;
    }
}
