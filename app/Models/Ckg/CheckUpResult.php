<?php

namespace App\Models\Ckg;

use App\Models\Screening;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function screening(): BelongsTo
    {
        return $this->belongsTo(Screening::class);
    }
}
