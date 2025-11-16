<?php

use App\Enums\MenuLocation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('location')->default(MenuLocation::Header->value)->index();
            $table->timestamps();
            $table->unique(['location']); // one menu per location by default
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};


