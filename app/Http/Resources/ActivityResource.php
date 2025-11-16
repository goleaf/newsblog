<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $subject = null;
        if ($this->subject_type === \App\Models\User::class && $this->relationLoaded('subject')) {
            $subject = [
                'type' => 'user',
                'id' => $this->subject->id,
                'name' => $this->subject->name,
            ];
        }

        return [
            'id' => $this->id,
            'description' => $this->description,
            'event' => $this->event,
            'created_at' => $this->created_at?->toISOString(),
            'subject' => $subject,
            'causer' => $this->whenLoaded('causer', function () {
                return [
                    'id' => $this->causer->id,
                    'name' => $this->causer->name,
                ];
            }),
        ];
    }
}
