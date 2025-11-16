<?php

use App\Enums\MenuItemType;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('menu_id')->constrained('menus')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->cascadeOnDelete();
            $table->string('type')->default(MenuItemType::Link->value)->index();
            $table->string('title');
            $table->string('url')->nullable(); // used when type = link
            $table->unsignedBigInteger('reference_id')->nullable(); // used when type references another model
            $table->unsignedInteger('order')->default(0)->index();
            $table->string('css_class')->nullable();
            $table->string('target')->nullable(); // e.g., _self, _blank
            $table->timestamps();
            $table->index(['menu_id', 'parent_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};


