<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class SampleContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = Category::factory()
            ->count(10)
            ->create();

        $tags = Tag::factory()
            ->count(50)
            ->create();

        $posts = Post::factory()
            ->count(100)
            ->create();

        $posts->each(function (Post $post) use ($categories, $tags): void {
            $post->categories()->sync(
                $categories->random(random_int(1, 3))->pluck('id')->all()
            );

            $post->tags()->sync(
                $tags->random(random_int(2, 5))->pluck('id')->all()
            );
        });
    }
}

