<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use \Exception;
use DB;

class Searchterm extends Model
{
    const ACCEPTED_FORMAT = "Content-Type: text/plain";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'campaign_name', 'adgroup_name', 'customer_search_term', 'keyworod', 'match_type', 'impressions', 'clicks', 'ctr', 'spend', 'cpc', 'acos'
    ];

    protected $dataFields = [
        'Campaign Name' => [
            'code' => 'campaign_name'
        ],
        'Ad Group Name' => [
            'code' => 'adgroup_name'
        ],
        'Customer Search Term' => [
            'code' => 'customer_search_term'
        ],
        'Keyword' => [
            'code' => 'keyworod'
        ],
        'Match Type' => [
            'code' => 'match_type'
        ],
        'Impressions' => [
            'code' =>'impressions'
        ],
        'Clicks' => [
            'code' =>'clicks'
        ],
        'CTR' => [
            'code' =>'ctr'
        ],
        'Total Spend' => [
            'code' =>'spend'
        ],
        'Average CPC' => [
            'code' =>'cpc'
        ],
        'ACoS' => [
            'code' =>'acos'
        ]
    ];

    protected $table = 'search_terms_report';

    public function upload($file) {
        $data = [];
        $dataToInsert = [];
        $lines = explode(PHP_EOL, $file);
        if(trim($lines[0]) != self::ACCEPTED_FORMAT) {
            throw new SearchtermUploadException('Incorrect file type, only Amazon report text file type is allowed!');
        }
        //unset file type line
        unset($lines[0]);
        //unset empty second line
        unset($lines[1]);

        foreach ($lines as $line) {
            $data[] = explode("\t", $line);
        }
        //imported data fields
        $dataFields = $data[0];
        //required data fields
        $fields = $this->dataFields;
        foreach($fields as $label => $field) {
            $index = array_search($label, $dataFields);
            if($index === false)
                throw new SearchtermUploadException("Field $label is missing, please double check your file!");
            $fields[$label]['index'] = $index;
        }
        //unset title field
        unset($data[0]);
        //prepare insert Data
        foreach($data as $index => $item) {
            //if($index>10)
            //    continue;
            try{
                $lineToInsert = [];
                foreach($fields as $label => $field) {
                    $fieldValue = $item[$field['index']];
                    if($field['code']=='ctr' || $field['code']=='acos' )
                        $fieldValue = rtrim($fieldValue, '%');
                    if($field['code']=='customer_search_term')
                        $fieldValue = trim(htmlspecialchars_decode($fieldValue), "\t\n\r\0\x0B\xC2\xA0");
                    $lineToInsert[$field['code']] = $fieldValue;
                }
                $dataToInsert[] = $lineToInsert;
            } catch (Exception $e){

            }
        }

        $datasToInsert = array_chunk($dataToInsert, 5000);
        foreach($datasToInsert as $dataToInsert) {
            $this->bulkInsert($dataToInsert);
        }
    }

    protected function bulkInsert($data) {
        $pdo = DB::connection()->getPdo();
        $pdo->beginTransaction(); // also helps speed up your inserts.
        $datafields = [];
        $fields = $this->dataFields;
        foreach($fields as $field) {
            $datafields[] = $field['code'];
        }

        $insert_values= [];
        foreach($data as $d){
            $question_marks[] = '('  . $this->placeholders('?', sizeof($d)) . ')';
            $insert_values = array_merge($insert_values, array_values($d));
        }

        $sql = "INSERT INTO search_terms_report (" . implode(",", $datafields ) . ") VALUES " . implode(',', $question_marks);

        $stmt = $pdo->prepare($sql);
        try {
            $stmt->execute($insert_values);
        } catch (PDOException $e){
            throw new SearchtermUploadException($e->getMessage());
        }
        $pdo->commit();
    }

    protected function placeholders($text, $count=0, $separator=","){
        $result = array();
        if($count > 0){
            for($x=0; $x<$count; $x++){
                $result[] = $text;
            }
        }

        return implode($separator, $result);
    }
}

class SearchtermUploadException extends Exception{

}
