<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use Illuminate\Http\Request;
use App\Model\Searchterm;
use App\Model\SearchtermUploadException;

class SearchtermController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * get search terms data
     *
     * @return json response
     */
    public function get(Request $request) {
        $result = [
          'searchterms' => [],
          'count' => 0
        ];
        $skip = $request->input('skip');
        $rows = $request->input('rows');
        $sortOrder = (int) $request->input('sortOrder') > 0 ? 'asc' : 'desc';
        $criteria = array(
            'globalFilter' => $request->input('globalFilter'),
            'sortField' => $request->input('sortField'),
            'sortOrder' => $sortOrder
        );
        $campaingName = $request->input('campaingName');
        $adGroupName = $request->input('adGroupName');
        $keyWordName = $request->input('keyWordName');
        $matchType = $request->input('matchType');
        try{
            $model = new Searchterm;
            if($campaingName || $adGroupName || $keyWordName) {
                $wheres = [];
                if($campaingName)
                    $wheres['campaign_name'] = $campaingName;
                if($adGroupName)
                    $wheres['adgroup_name'] = $adGroupName;
                if($keyWordName)
                    $wheres['keyworod'] = $keyWordName;
                if($matchType)
                    $wheres['match_type'] = $matchType;
                $model = $model->where($wheres);
            }
            if($criteria['globalFilter'])
                $model = $model->where('customer_search_term', 'LIKE', '%' . $criteria['globalFilter'] . '%');
            if($criteria['sortField'] && $criteria['sortOrder'])
            $model = $model->orderBy($criteria['sortField'], $criteria['sortOrder']);
            $result['count'] = $model->count();
            $result['searchterms'] = $model->offset($skip)->limit($rows)->get();
        }catch (\Exception $e) {
            return response()->json(['errors' => [$e->getMessage()]], 422);
        }
        return response()->json(['data' => $result], 200);
    }

    /**
     * upload search terms data
     *
     * @return json response
     */
    public function upload(Request $request) {

        $files = $this->parse_raw_http_request();
        $uploadedFileString = '';
        foreach($files as $name => $file) {
            $uploadedFileString = $file;
            break;
        }
        try{
            Searchterm::truncate();
            $searchTerm = new Searchterm();
            $searchTerm->upload($uploadedFileString);
        }catch (SearchtermUploadException $e){
            return response()->json(['errors' => [$e->getMessage()]], 422);
        }catch (\Exception $e) {
            return response()->json(['errors' => [$e->getMessage()]], 422);
        }
        return response()->json('{}', 200);
    }

    public function parse_raw_http_request()
    {
        $a_data = [];
        // read incoming data
        $input = file_get_contents('php://input');

        // grab multipart boundary from content type header
        preg_match('/boundary=(.*)$/', $_SERVER['CONTENT_TYPE'], $matches);
        $boundary = $matches[1];

        // split content by boundary and get rid of last -- element
        $a_blocks = preg_split("/-+$boundary/", $input);
        array_pop($a_blocks);

        // loop data blocks
        foreach ($a_blocks as $id => $block)
        {
            if (empty($block))
                continue;

            // you'll have to var_dump $block to understand this and maybe replace \n or \r with a visibile char

            // parse uploaded files
            if (strpos($block, 'application/octet-stream') !== FALSE)
            {
                // match "name", then everything after "stream" (optional) except for prepending newlines
                preg_match("/name=\"([^\"]*)\".*stream[\n|\r]+([^\n\r].*)?$/s", $block, $matches);
            }
            // parse all other fields
            else
            {
                // match "name" and optional value in between newline sequences
                preg_match('/name=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?\r$/s', $block, $matches);
            }
            $a_data[$matches[1]] = $matches[2];
        }
        return $a_data;
    }
}
