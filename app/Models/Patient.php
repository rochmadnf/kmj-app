<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasUuids;
    
    protected $fillable = [
        'nik',
        'full_name',
        'born_date',
        'gender',
    ];

    protected $keyType = 'string';
    public $incrementing = false;

    protected function casts()
    {
        return [
            'born_date' => 'datetime',
        ];
    }

    public function screenings()
    {
        return $this->hasMany(Screening::class);
    }

    
}
