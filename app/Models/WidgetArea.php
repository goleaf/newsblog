<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WidgetArea extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    public function widgets(): HasMany
    {
        return $this->hasMany(Widget::class)->orderBy('order');
    }

    public function activeWidgets(): HasMany
    {
        return $this->hasMany(Widget::class)
            ->where('active', true)
            ->orderBy('order');
    }
}
