<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {{ config('app.name') }}</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        .content {
            background-color: #f9fafb;
            padding: 30px;
            border: 1px solid #e5e7eb;
            border-top: none;
        }
        .welcome-message {
            font-size: 18px;
            color: #1f2937;
            margin-bottom: 20px;
        }
        .feature-box {
            background-color: white;
            border-left: 4px solid #4f46e5;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .feature-box h3 {
            margin: 0 0 10px 0;
            color: #4f46e5;
            font-size: 16px;
        }
        .feature-box p {
            margin: 0;
            color: #6b7280;
            font-size: 14px;
        }
        .button {
            display: inline-block;
            background-color: #4f46e5;
            color: white;
            padding: 14px 28px;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 20px;
            font-weight: bold;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #6b7280;
            font-size: 14px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        .emoji {
            font-size: 24px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="emoji">🎉</div>
        <h1>Welcome to {{ config('app.name') }}!</h1>
    </div>
    
    <div class="content">
        <div class="welcome-message">
            <p>Hello {{ $user->name }},</p>
            <p>Thank you for joining {{ config('app.name') }}! We're excited to have you as part of our community.</p>
        </div>
        
        <p>Your account has been successfully created and you're all set to explore our platform.</p>
        
        <div class="feature-box">
            <h3>📚 Discover Content</h3>
            <p>Browse through our collection of articles, tutorials, and insights on technology, programming, and information systems.</p>
        </div>
        
        <div class="feature-box">
            <h3>💬 Join Discussions</h3>
            <p>Share your thoughts and engage with other readers by commenting on posts that interest you.</p>
        </div>
        
        <div class="feature-box">
            <h3>🔖 Save Your Favorites</h3>
            <p>Bookmark articles you want to read later and build your personal reading list.</p>
        </div>
        
        <div class="feature-box">
            <h3>📧 Stay Updated</h3>
            <p>Subscribe to our newsletter to receive the latest posts and updates directly in your inbox.</p>
        </div>
        
        <div style="text-align: center;">
            <a href="{{ route('home') }}" class="button">Start Exploring</a>
        </div>
        
        <p style="margin-top: 30px; color: #6b7280; font-size: 14px;">
            If you have any questions or need assistance, feel free to reach out to our support team.
        </p>
    </div>
    
    <div class="footer">
        <p>Welcome aboard!</p>
        <p style="margin-top: 10px;">The {{ config('app.name') }} Team</p>
        <p style="margin-top: 15px; font-size: 12px;">
            <a href="{{ route('home') }}" style="color: #4f46e5; text-decoration: none;">Visit our website</a>
        </p>
    </div>
</body>
</html>
