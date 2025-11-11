<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Published</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4F46E5;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9fafb;
            padding: 30px;
            border: 1px solid #e5e7eb;
            border-top: none;
        }
        .post-title {
            font-size: 24px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 15px;
        }
        .post-info {
            color: #6b7280;
            margin-bottom: 20px;
        }
        .button {
            display: inline-block;
            background-color: #4F46E5;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #6b7280;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎉 Your Post is Now Live!</h1>
    </div>
    
    <div class="content">
        <p>Hello {{ $post->user->name }},</p>
        
        <p>Great news! Your scheduled post has been automatically published and is now live on the site.</p>
        
        <div class="post-title">{{ $post->title }}</div>
        
        <div class="post-info">
            <strong>Published:</strong> {{ $post->published_at->format('F j, Y \a\t g:i A') }}<br>
            <strong>Category:</strong> {{ $post->category->name ?? 'Uncategorized' }}<br>
            <strong>Reading Time:</strong> {{ $post->reading_time }} min
        </div>
        
        @if($post->excerpt)
        <p><strong>Excerpt:</strong><br>{{ $post->excerpt }}</p>
        @endif
        
        <a href="{{ route('post.show', $post->slug) }}" class="button">View Your Post</a>
        
        <p style="margin-top: 30px;">You can now share your post with your audience and track its performance in the admin dashboard.</p>
    </div>
    
    <div class="footer">
        <p>This is an automated notification from {{ config('app.name') }}</p>
    </div>
</body>
</html>
