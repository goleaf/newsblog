<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Someone replied to your comment</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #f8f9fa; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
        <h2 style="color: #4f46e5; margin-top: 0;">New Reply to Your Comment</h2>
        <p>Hi {{ $parentComment->author_name }},</p>
        <p>{{ $reply->author_name }} replied to your comment on "{{ $reply->post->title }}":</p>
    </div>

    <div style="background-color: #ffffff; border-left: 4px solid #4f46e5; padding: 15px; margin-bottom: 20px;">
        <p style="margin: 0; color: #666; font-size: 14px;"><strong>Your comment:</strong></p>
        <p style="margin: 10px 0;">{{ $parentComment->content }}</p>
    </div>

    <div style="background-color: #f0f9ff; border-left: 4px solid #0ea5e9; padding: 15px; margin-bottom: 20px;">
        <p style="margin: 0; color: #666; font-size: 14px;"><strong>{{ $reply->author_name }}'s reply:</strong></p>
        <p style="margin: 10px 0;">{{ $reply->content }}</p>
    </div>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ route('post.show', $reply->post->slug) }}#comment-{{ $reply->id }}" 
           style="display: inline-block; background-color: #4f46e5; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: bold;">
            View Reply
        </a>
    </div>

    <div style="border-top: 1px solid #e5e7eb; padding-top: 20px; margin-top: 30px; font-size: 12px; color: #6b7280;">
        <p>You're receiving this email because someone replied to your comment on {{ config('app.name') }}.</p>
        <p style="margin-top: 10px;">
            <a href="{{ route('post.show', $reply->post->slug) }}" style="color: #4f46e5; text-decoration: none;">View the full conversation</a>
        </p>
    </div>
</body>
</html>
