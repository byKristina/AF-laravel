<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'email' => $this->email,
            'last_name' => $this->last_name,
            'gender' => $this->gender,
            'profile_picture' => $this->profile_picture,
            'role_id' => $this->role_id,
            'age' => Carbon::parse($this->birth_date)->age, 
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
