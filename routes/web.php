<?php

use App\Exports\Ckg\RaporExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Excel;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/report/export', function (Request $request) {
    $category = $request->has('s') ? $request->get('s') : 'SD';
    return (new RaporExport($category))->download("{$category}_Rapor_CKG_Sekolah_" . time() . ".xlsx", Excel::XLSX);
});
