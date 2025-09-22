<?php

namespace App\Models;

use App\Models\Ckg\CheckUpResult;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Screening extends Model
{
    use HasUuids;

    protected $fillable = [
        'register_id',
        'register_date',
        'patient_id',
        'ticket_number',
        'token_report',
        'klaster_code',
        'klaster_name',
        'school_name',
        'school_code',
        'school_category',
        'class_name',
        'class_code',
    ];

    protected $keyType = 'string';
    public $incrementing = false;

    public  function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function casts()
    {
        return [
            'register_date' => 'datetime',
        ];
    }

    public function checkUpResult(){
        return $this->hasOne(CheckUpResult::class);
    }
}
