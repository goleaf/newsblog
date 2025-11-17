<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ $subject }}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f3f4f6;
            color: #1f2937;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            color: #ffffff;
            font-size: 28px;
            font-weight: 700;
        }
        .header p {
            margin: 10px 0 0;
            color: #e5e7eb;
            font-size: 14px;
        }
        .content {
            padding: 40px 20px;
        }
        .greeting {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #111827;
        }
        .intro {
            font-size: 16px;
            line-height: 1.6;
            color: #4b5563;
            margin-bottom: 30px;
        }
        .article-card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 24px;
            overflow: hidden;
            transition: box-shadow 0.3s ease;
        }
        .article-card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .article-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            display: block;
        }
        .article-content {
            padding: 20px;
        }
        .article-meta {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
            font-size: 13px;
            color: #6b7280;
        }
        .article-category {
            background-color: #dbeafe;
            color: #1e40af;
            padding: 4px 12px;
            border-radius: 12px;
            font-weight: 500;
            text-decoration: none;
        }
        .article-reading-time {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .article-title {
            font-size: 20px;
            font-weight: 700;
            color: #111827;
            margin: 0 0 12px;
            line-height: 1.3;
        }
        .article-title a {
            color: #111827;
            text-decoration: none;
        }
        .article-title a:hover {
            color: #667eea;
        }
        .article-excerpt {
            font-size: 15px;
            line-height: 1.6;
            color: #4b5563;
            margin: 0 0 16px;
        }
        .article-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 16px;
            border-top: 1px solid #e5e7eb;
        }
        .article-author {
            font-size: 14px;
            color: #6b7280;
        }
        .article-author strong {
            color: #111827;
        }
        .article-stats {
            display: flex;
            gap: 16px;
            font-size: 13px;
            color: #6b7280;
        }
        .read-more {
            display: inline-block;
            background-color: #667eea;
            color: #ffffff;
            padding: 10px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }
        .read-more:hover {
            background-color: #5568d3;
        }
        .divider {
            height: 1px;
            background-color: #e5e7eb;
            margin: 40px 0;
        }
        .footer {
            background-color: #f9fafb;
            padding: 30px 20px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        .footer-links {
            margin-bottom: 20px;
        }
        .footer-links a {
            color: #667eea;
            text-decoration: none;
            margin: 0 12px;
            font-size: 14px;
        }
        .footer-links a:hover {
            text-decoration: underline;
        }
        .footer-text {
            font-size: 13px;
            color: #6b7280;
            line-height: 1.6;
            margin: 10px 0;
        }
        .unsubscribe {
            font-size: 12px;
            color: #9ca3af;
            margin-top: 20px;
        }
        .unsubscribe a {
            color: #9ca3af;
            text-decoration: underline;
        }
        /* Tracking pixel */
        .tracking-pixel {
            width: 1px;
            height: 1px;
            display: block;
        }
        @media only screen and (max-width: 600px) {
            .header h1 {
                font-size: 24px;
            }
            .article-title {
                font-size: 18px;
            }
            .article-footer {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>{{ config('app.name', 'TechNewsHub') }}</h1>
            <p>{{ $subject }}</p>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting">{{ $greeting ?? 'Hello!' }}</div>
            
            <div class="intro">
                Here are the top tech stories we've curated for you this {{ strtolower($subscriber->frequency ?? 'week') }}. 
                Stay informed with the latest insights, trends, and innovations in technology.
            </div>

            <!-- Articles -->
            @foreach($articles as $article)
                <div class="article-card">
                    @if($article->featured_image)
                        <a href="{{ route('post.show', $article->slug) }}">
                            <img src="{{ asset('storage/' . $article->featured_image) }}" 
                                 alt="{{ $article->image_alt_text ?? $article->title }}" 
                                 class="article-image">
                        </a>
                    @endif
                    
                    <div class="article-content">
                        <div class="article-meta">
                            @if($article->category)
                                <a href="{{ route('category.show', $article->category->slug) }}" class="article-category">
                                    {{ $article->category->name }}
                                </a>
                            @endif
                            @if($article->reading_time)
                                <span class="article-reading-time">
                                    📖 {{ $article->reading_time }} min read
                                </span>
                            @endif
                        </div>

                        <h2 class="article-title">
                            <a href="{{ route('post.show', $article->slug) }}">
                                {{ $article->title }}
                            </a>
                        </h2>

                        <p class="article-excerpt">
                            {{ Str::limit(strip_tags($article->excerpt), 150) }}
                        </p>

                        <div class="article-footer">
                            <div class="article-author">
                                By <strong>{{ $article->user->name }}</strong>
                            </div>
                            <div class="article-stats">
                                @if(isset($article->views_count))
                                    <span>👁 {{ number_format($article->views_count) }}</span>
                                @endif
                                @if(isset($article->comments_count) && $article->comments_count > 0)
                                    <span>💬 {{ $article->comments_count }}</span>
                                @endif
                            </div>
                        </div>

                        <div style="margin-top: 16px;">
                            <a href="{{ route('post.show', $article->slug) }}" class="read-more">
                                Read Full Article →
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="divider"></div>

            <div style="text-align: center; margin: 30px 0;">
                <p style="font-size: 16px; color: #4b5563; margin-bottom: 20px;">
                    Want to explore more articles?
                </p>
                <a href="{{ route('home') }}" class="read-more">
                    Visit Our Website
                </a>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-links">
                <a href="{{ $preferencesUrl }}">Manage Preferences</a>
                <a href="{{ route('home') }}">Visit Website</a>
                <a href="{{ route('contact') }}">Contact Us</a>
            </div>

            <p class="footer-text">
                You're receiving this email because you subscribed to {{ config('app.name', 'TechNewsHub') }} newsletter.
            </p>

            <p class="footer-text">
                © {{ date('Y') }} {{ config('app.name', 'TechNewsHub') }}. All rights reserved.
            </p>

            <div class="unsubscribe">
                Don't want to receive these emails? 
                <a href="{{ $unsubscribeUrl }}">Unsubscribe</a>
            </div>
        </div>
    </div>

    <!-- Tracking pixel (will be implemented in tracking task) -->
    @if(isset($trackingToken))
        <img src="{{ route('newsletter.track.open', $trackingToken) }}" 
             alt="" 
             class="tracking-pixel">
    @endif
</body>
</html>
