<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(function ($model) {
            $model->logActivity('created');
        });

        static::updated(function ($model) {
            $model->logActivity('updated');
        });

        static::deleted(function ($model) {
            $model->logActivity('deleted');
        });
    }

    public function logActivity(string $event, ?string $description = null): void
    {
        ActivityLog::create([
            'log_name' => $this->getLogName(),
            'description' => $description ?? $this->getActivityDescription($event),
            'subject_type' => get_class($this),
            'subject_id' => $this->id,
            'event' => $event,
            'causer_type' => Auth::check() ? get_class(Auth::user()) : null,
            'causer_id' => Auth::id(),
            'properties' => $this->getActivityProperties($event),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    protected function getLogName(): string
    {
        return class_basename($this);
    }

    protected function getActivityDescription(string $event): string
    {
        $modelName = class_basename($this);

        return match ($event) {
            'created' => "{$modelName} created",
            'updated' => "{$modelName} updated",
            'deleted' => "{$modelName} deleted",
            default => "{$modelName} {$event}",
        };
    }

    protected function getActivityProperties(string $event): array
    {
        // Use raw attribute arrays to avoid triggering expensive casts during tests
        // and to ensure enum-backed attributes do not cause instantiation errors.
        // - For "created": capture the current raw attributes
        // - For "updated": capture original raw attributes and the current raw attributes
        // - For "deleted": capture the original raw attributes
        return match ($event) {
            'updated' => [
                'old' => $this->getOriginal(),
                'new' => $this->getAttributes(),
            ],
            'deleted' => $this->getOriginal(),
            default => $this->getAttributes(),
        };
    }
}
