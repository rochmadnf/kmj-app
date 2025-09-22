<?php

namespace App\Http\Controllers;

use App\Http\Resources\ScreeningResource;

class ScreeningController extends Controller
{
    public function index(){
        $screenings = \App\Models\Screening::with('patient')->doesntHave('checkUpResult')->get();

        return response()->json(["data" => ScreeningResource::collection($screenings)]);
    }
}
