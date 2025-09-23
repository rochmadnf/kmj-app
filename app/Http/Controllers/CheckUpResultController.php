<?php

namespace App\Http\Controllers;

use App\Models\Ckg\CheckUpResult;
use App\Models\Screening;
use Illuminate\Http\Request;

class CheckUpResultController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->all();

        $sc = Screening::where('register_id', $data['data']['patient_detail']['reg_id'])->first();

        $cur = CheckUpResult::where('screening_id', $sc->id)->first();

        $isCreated = false;

        if (is_null($cur)) {
            CheckUpResult::create([
                'screening_id' => $sc->id,
                'results' => is_null($data['data']['rapor_kesehatan']['hasil_pemeriksaan']) ? [] : $data['data']['rapor_kesehatan']['hasil_pemeriksaan']
            ]);
            $isCreated = true;
        }


        return response()->json(["message" => $isCreated ? "Checkup Result created" : "Checkup Result already exists"], $isCreated ? 201 : 200);
    }

    public function index()
    {
        $cur = CheckUpResult::with('screening', 'screening.patient')->whereRaw('JSON_LENGTH(results) = 0')->get();


        return response()->json(['message' => 'Check Up Results', 'data' => $cur]);
    }
}
