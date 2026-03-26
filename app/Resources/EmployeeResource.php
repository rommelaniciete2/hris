<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'full_name' => $this->first_name.' '.$this->last_name,
            'email' => $this->email,
            'position' => $this->position,
            'salary' => $this->salary,
            'created_at' => $this->created_at->format('Y-m-d'),
        ];
    }
}
