<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
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
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'gender' => $this->gender,
            'profile_picture' => $this->profile_picture,
            'age' => Carbon::parse($this->birth_date)->age, 
            'status' => $this->pivot->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
