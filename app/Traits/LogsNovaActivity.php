<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Laravel\Nova\Http\Requests\NovaRequest;

trait LogsNovaActivity
{
    /**
     * Register a callback to be called after the resource is created.
     */
    public static function afterCreate(NovaRequest $request, Model $model): void
    {
        static::logNovaActivity($request, $model, 'created');
    }

    /**
     * Register a callback to be called after the resource is updated.
     */
    public static function afterUpdate(NovaRequest $request, Model $model): void
    {
        static::logNovaActivity($request, $model, 'updated');
    }

    /**
     * Register a callback to be called after the resource is deleted.
     */
    public static function afterDelete(NovaRequest $request, Model $model): void
    {
        static::logNovaActivity($request, $model, 'deleted');
    }

    /**
     * Register a callback to be called after the resource is force deleted.
     */
    public static function afterForceDelete(NovaRequest $request, Model $model): void
    {
        static::logNovaActivity($request, $model, 'force_deleted');
    }

    /**
     * Register a callback to be called after the resource is restored.
     */
    public static function afterRestore(NovaRequest $request, Model $model): void
    {
        static::logNovaActivity($request, $model, 'restored');
    }

    /**
     * Log Nova activity to ActivityLog model.
     */
    protected static function logNovaActivity(NovaRequest $request, Model $model, string $event): void
    {
        $user = $request->user();
        $modelName = class_basename($model);

        $description = match ($event) {
            'created' => "{$modelName} created via Nova",
            'updated' => "{$modelName} updated via Nova",
            'deleted' => "{$modelName} deleted via Nova",
            'force_deleted' => "{$modelName} force deleted via Nova",
            'restored' => "{$modelName} restored via Nova",
            default => "{$modelName} {$event} via Nova",
        };

        $properties = static::getActivityProperties($model, $event);

        ActivityLog::create([
            'log_name' => 'Nova',
            'description' => $description,
            'subject_type' => get_class($model),
            'subject_id' => $model->id,
            'event' => $event,
            'causer_type' => $user ? get_class($user) : null,
            'causer_id' => $user?->id,
            'properties' => $properties,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    /**
     * Get activity properties for the given event.
     */
    protected static function getActivityProperties(Model $model, string $event): array
    {
        if ($event === 'updated') {
            return [
                'old' => $model->getOriginal(),
                'new' => $model->getChanges(),
            ];
        }

        if ($event === 'restored') {
            return [
                'restored_at' => now()->toDateTimeString(),
                'attributes' => $model->getAttributes(),
            ];
        }

        return [
            'attributes' => $model->getAttributes(),
        ];
    }
}
