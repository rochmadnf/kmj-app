<?php

namespace App\Models\Ckg;

use Illuminate\Database\Eloquent\Model;

class CheckUpResult extends Model
{
    use \Illuminate\Database\Eloquent\Concerns\HasUuids;
    
    protected $fillable = [
        'screening_id',
        'results',
    ];

    protected $keyType = 'string';
    public $incrementing = false;

    public function casts()
    {
        return [
            'results' => 'array',
        ];
    }
}
