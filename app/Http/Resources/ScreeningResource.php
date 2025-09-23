<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScreeningResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient' => [
                'id' => $this->patient->id,
                'name' => $this->patient->full_name,
                'gender' => (int) $this->patient->gender === 0 ? 'PEREMPUAN' : 'LAKI-LAKI',
                'born_date' => $this->patient->born_date->translatedFormat('d F Y'),
                'age' => $this->patient->born_date->age,
                'education' => [
                    'school' => [
                        'code' => $this->school_code,
                        'name' => $this->school_name,
                        'level' => $this->school_category,
                    ],
                    'class' => [
                        'code' => $this->class_code,
                        'name' => $this->class_name,
                    ]
                ]
            ],
            'register_id' => $this->register_id,
            'ticket_number' => $this->ticket_number,
            'token_report' => $this->token_report,
        ];
    }
}
