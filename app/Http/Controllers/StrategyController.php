<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use Illuminate\Http\Request;
use App\Model\Strategy;

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
            $model->strategy = json_encode($data);
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
            $strategy = json_decode($model->strategy, true);
        } catch (Exception $e) {
            return response()->json(['errors' => [$e->getMessage()]], 422);
        }
        return response()->json($strategy);
    }

}