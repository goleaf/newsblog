<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comment Approved</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #10b981; color: white; border-radius: 8px; padding: 20px; margin-bottom: 20px; text-align: center;">
        <h2 style="margin: 0;">✓ Comment Approved!</h2>
    </div>

    <div style="background-color: #f8f9fa; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
        <p>Hi {{ $comment->post->user->name }},</p>
        <p>Great news! A comment on your post "{{ $comment->post->title }}" has been approved and is now visible to readers.</p>
    </div>

    <div style="background-color: #ffffff; border-left: 4px solid #10b981; padding: 15px; margin-bottom: 20px;">
        <p style="margin: 0; color: #666; font-size: 14px;"><strong>Comment by {{ $comment->author_name }}:</strong></p>
        <p style="margin: 10px 0;">{{ $comment->content }}</p>
        <p style="margin: 10px 0 0 0; color: #6b7280; font-size: 12px;">
            Posted on {{ $comment->created_at->format('F j, Y \a\t g:i A') }}
        </p>
    </div>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ route('post.show', $comment->post->slug) }}#comment-{{ $comment->id }}" 
           style="display: inline-block; background-color: #4f46e5; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: bold;">
            View Comment
        </a>
    </div>

    <div style="background-color: #f0f9ff; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
        <p style="margin: 0; font-size: 14px; color: #0369a1;">
            <strong>💡 Tip:</strong> Engage with your readers by replying to their comments. It helps build a community around your content!
        </p>
    </div>

    <div style="border-top: 1px solid #e5e7eb; padding-top: 20px; margin-top: 30px; font-size: 12px; color: #6b7280;">
        <p>You're receiving this email because a comment on your post was approved on {{ config('app.name') }}.</p>
        <p style="margin-top: 10px;">
            <a href="{{ route('post.show', $comment->post->slug) }}" style="color: #4f46e5; text-decoration: none;">View your post</a>
        </p>
    </div>
</body>
</html>
