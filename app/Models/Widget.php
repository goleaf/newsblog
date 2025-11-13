<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Widget extends Model
{
    use HasFactory;

    protected $fillable = [
        'widget_area_id',
        'type',
        'title',
        'settings',
        'order',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'active' => 'boolean',
        ];
    }

    public function widgetArea(): BelongsTo
    {
        return $this->belongsTo(WidgetArea::class);
    }
}
