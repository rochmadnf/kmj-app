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
                'name' => $this->patient->full_name,
            ],
            'register_id' => $this->register_id,
            'token_report' => $this->token_report,
        ];
    }
}
