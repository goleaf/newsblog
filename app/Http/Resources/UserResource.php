<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $base = [
            'id' => $this->id,
            'name' => $this->name,
            'avatar_url' => $this->avatar_url ?? null,
            'role' => $this->role,
            'created_at' => $this->created_at?->toISOString(),
        ];

        if ($request->user() && $request->user()->id === $this->id) {
            $base['email'] = $this->email;
        }

        return $base;
    }
}
