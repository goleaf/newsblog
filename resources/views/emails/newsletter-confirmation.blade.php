<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Newsletter Subscription Confirmed</title>
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
        .message {
            font-size: 16px;
            color: #1f2937;
            margin-bottom: 20px;
        }
        .success-box {
            background-color: #d1fae5;
            border-left: 4px solid #10b981;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
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
            margin: 20px 0;
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
        <div class="emoji">✅</div>
        <h1>Subscription Confirmed!</h1>
    </div>
    
    <div class="content">
        <div class="success-box">
            <p style="margin: 0; font-size: 16px; color: #065f46;">
                <strong>🎉 Success!</strong> Your email address has been verified and you're now subscribed to our newsletter.
            </p>
        </div>
        
        <div class="message">
            <p>Hello,</p>
            <p>Welcome to the {{ config('app.name') }} newsletter community!</p>
            <p>You'll now receive our latest articles, updates, and insights directly in your inbox.</p>
        </div>
        
        <div class="feature-box">
            <h3>📰 What to Expect</h3>
            <p>Regular updates featuring our best content on technology, programming, and information systems.</p>
        </div>
        
        <div class="feature-box">
            <h3>🎯 Curated Content</h3>
            <p>Handpicked articles and tutorials tailored to help you stay ahead in the tech world.</p>
        </div>
        
        <div class="feature-box">
            <h3>🔔 Exclusive Updates</h3>
            <p>Be the first to know about new features, special content, and community highlights.</p>
        </div>
        
        <div style="text-align: center;">
            <a href="{{ route('home') }}" class="button">Visit Our Website</a>
        </div>
        
        <p style="margin-top: 30px; color: #6b7280; font-size: 14px;">
            You can unsubscribe at any time by clicking the unsubscribe link at the bottom of any newsletter email.
        </p>
    </div>
    
    <div class="footer">
        <p>Thank you for subscribing!</p>
        <p style="margin-top: 10px;">The {{ config('app.name') }} Team</p>
        <p style="margin-top: 15px; font-size: 12px;">
            <a href="{{ route('newsletter.unsubscribe', $newsletter->unsubscribe_token) }}" style="color: #6b7280; text-decoration: none;">Unsubscribe</a>
        </p>
    </div>
</body>
</html>
