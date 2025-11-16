<?php

namespace App\Models;

use App\Enums\MenuItemType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'menu_id',
        'parent_id',
        'type',
        'title',
        'url',
        'reference_id',
        'order',
        'css_class',
        'target',
    ];

    protected function casts(): array
    {
        return [
            'type' => MenuItemType::class,
            'order' => 'integer',
            'reference_id' => 'integer',
        ];
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->orderBy('order');
    }

    /**
     * Resolve URL dynamically for typed items when not explicitly set.
     */
    public function getUrlAttribute($value): ?string
    {
        if (! empty($value)) {
            return $value;
        }

        if ($this->type === MenuItemType::Page && $this->reference_id) {
            $page = Page::query()->select(['id', 'slug', 'parent_id'])->with('parent')->find($this->reference_id);
            if ($page) {
                return url('/page/'.$page->slug_path);
            }
        }

        if ($this->type === MenuItemType::Category && $this->reference_id) {
            $cat = Category::query()->select(['id', 'slug'])->find($this->reference_id);
            if ($cat) {
                return route('category.show', $cat->slug);
            }
        }

        if ($this->type === MenuItemType::Tag && $this->reference_id) {
            $tag = Tag::query()->select(['id', 'slug'])->find($this->reference_id);
            if ($tag) {
                return route('tag.show', $tag->slug);
            }
        }

        return null;
    }
}
