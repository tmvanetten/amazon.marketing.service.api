<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use Illuminate\Http\Request;
use App\Model\Strategy;
use App\Model\StrategyHistory;

class StrategyController extends Controller
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

    public function getModel()
    {
        $model = Strategy::first();
        if(!$model)
            $model = new Strategy;
        return $model;
    }

    public function saveStrategy(Request $request) {
        $data = $request->all();
        try {
            $model = $this->getModel();
            $model->strategy = json_encode($data['strategies']);
            $model->recent_date_range = $data['recent_date_range'];
            $model->past_date_range = $data['past_date_range'];
            $model->run_days = $data['run_days'];
            $model->date_offset = $data['date_offset'];
            $model->save();
        } catch (Exception $e) {
            return response()->json(['errors' => [$e->getMessage()]], 422);
        }
        return response()->json('{}', 200);
    }

    public function getStrategy()
    {
        try {
            $model = $this->getModel();
            $model->strategies = json_decode($model->strategy, true);
        } catch (Exception $e) {
            return response()->json(['errors' => [$e->getMessage()]], 422);
        }
        return response()->json($model);
    }

    public function getStrategyHistory(Request $request) {
        $skip = $request->input('skip');
        $rows = $request->input('rows');
        $filters = $request->input('filters');
        $sortOrder = (int) $request->input('sortOrder') > 0 ? 'asc' : 'desc';
        $criteria = array(
            'globalFilter' => $request->input('globalFilter'),
            'sortField' => $request->input('sortField'),
            'sortOrder' => $sortOrder,
            'filters' => $filters
        );
        $result['histories'] = StrategyHistory::getHistories($criteria, $skip, $rows);
        $result['counts'] = count(StrategyHistory::getHistories($criteria));

        return response()->json($result);
    }

}