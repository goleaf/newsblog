<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = \App\Models\User::first();
        $categories = \App\Models\Category::all();
        $tags = \App\Models\Tag::all();

        $posts = [
            [
                'title' => 'Getting Started with Laravel 11: A Comprehensive Guide',
                'content' => '<h2>Introduction to Laravel 11</h2><p>Laravel 11 brings exciting new features and improvements to the popular PHP framework. In this comprehensive guide, we will explore the key changes and how to get started with your first Laravel 11 project.</p><h3>Installation</h3><p>To install Laravel 11, you need PHP 8.2 or higher and Composer. Run the following command:</p><pre>composer create-project laravel/laravel my-app</pre><h3>New Features</h3><p>Laravel 11 introduces several new features including improved performance, better type safety, and enhanced developer experience.</p>',
                'excerpt' => 'Learn how to get started with Laravel 11 and explore its new features and improvements.',
                'category_id' => $categories->where('slug', 'web-development')->first()->id,
                'is_featured' => true,
                'is_trending' => true,
                'view_count' => 1250,
            ],
            [
                'title' => 'Understanding React Hooks: useState and useEffect',
                'content' => '<h2>React Hooks Explained</h2><p>React Hooks revolutionized the way we write React components. This article focuses on the two most commonly used hooks: useState and useEffect.</p><h3>useState Hook</h3><p>The useState hook allows you to add state to functional components. Here is a simple example:</p><pre>const [count, setCount] = useState(0);</pre><h3>useEffect Hook</h3><p>The useEffect hook lets you perform side effects in function components. It serves the same purpose as componentDidMount, componentDidUpdate, and componentWillUnmount in React class components.</p>',
                'excerpt' => 'Master the fundamentals of React Hooks with practical examples of useState and useEffect.',
                'category_id' => $categories->where('slug', 'web-development')->first()->id,
                'is_trending' => true,
                'view_count' => 980,
            ],
            [
                'title' => 'Docker Best Practices for Production Environments',
                'content' => '<h2>Production-Ready Docker</h2><p>Running Docker in production requires careful planning and adherence to best practices. This guide covers essential tips for deploying containerized applications.</p><h3>Multi-Stage Builds</h3><p>Use multi-stage builds to optimize your Docker images and reduce their size. This technique helps separate build dependencies from runtime dependencies.</p><h3>Security Considerations</h3><p>Never run containers as root, use official base images, and regularly update your dependencies to patch security vulnerabilities.</p>',
                'excerpt' => 'Learn the best practices for running Docker containers in production environments.',
                'category_id' => $categories->where('slug', 'devops')->first()->id,
                'view_count' => 750,
            ],
            [
                'title' => 'Introduction to Machine Learning with Python',
                'content' => '<h2>Getting Started with ML</h2><p>Machine Learning is transforming industries worldwide. This tutorial introduces the basics of ML using Python and scikit-learn.</p><h3>What is Machine Learning?</h3><p>Machine Learning is a subset of artificial intelligence that enables systems to learn and improve from experience without being explicitly programmed.</p><h3>Your First ML Model</h3><p>We will create a simple linear regression model to predict housing prices based on features like size, location, and number of bedrooms.</p>',
                'excerpt' => 'Start your machine learning journey with this beginner-friendly Python tutorial.',
                'category_id' => $categories->where('slug', 'ai-machine-learning')->first()->id,
                'is_featured' => true,
                'view_count' => 1420,
            ],
            [
                'title' => 'Cybersecurity Essentials Every Developer Should Know',
                'content' => '<h2>Security First Mindset</h2><p>Security should be a priority in every development project. This article covers essential security practices that every developer must know.</p><h3>Input Validation</h3><p>Always validate and sanitize user input to prevent injection attacks like SQL injection and XSS.</p><h3>Authentication and Authorization</h3><p>Implement robust authentication mechanisms and follow the principle of least privilege for authorization.</p><h3>Data Encryption</h3><p>Encrypt sensitive data both at rest and in transit using industry-standard encryption algorithms.</p>',
                'excerpt' => 'Essential cybersecurity practices that every developer must implement in their applications.',
                'category_id' => $categories->where('slug', 'cybersecurity')->first()->id,
                'view_count' => 890,
            ],
        ];

        foreach ($posts as $postData) {
            $post = \App\Models\Post::create([
                'user_id' => $user->id,
                'category_id' => $postData['category_id'],
                'title' => $postData['title'],
                'slug' => \Illuminate\Support\Str::slug($postData['title']),
                'content' => $postData['content'],
                'excerpt' => $postData['excerpt'],
                'status' => 'published',
                'is_featured' => $postData['is_featured'] ?? false,
                'is_trending' => $postData['is_trending'] ?? false,
                'view_count' => $postData['view_count'],
                'published_at' => now()->subDays(rand(1, 30)),
            ]);

            $randomTags = $tags->random(rand(3, 6));
            $post->tags()->attach($randomTags);
        }
    }
}
