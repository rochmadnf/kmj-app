<?php

namespace App\Http\Controllers;

use App\Http\Resources\ScreeningResource;
use App\Models\Screening;

class ScreeningController extends Controller
{
    public function index()
    {
        $screenings = Screening::with('patient')->doesntHave('checkUpResult')->get();

        return response()->json(["data" => ScreeningResource::collection($screenings)]);
    }

    public function withResult()
    {
        $screenings = Screening::with('patient', 'checkUpResult')->whereHas('checkUpResult')->get();

        return response()->json(["msg" => "Success", "desc" => "Screening with result", "data" => $screenings]);
    }
}
