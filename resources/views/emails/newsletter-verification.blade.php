<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Newsletter Subscription</title>
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
        .info-box {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
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
        <div class="emoji">📧</div>
        <h1>Verify Your Subscription</h1>
    </div>
    
    <div class="content">
        <div class="message">
            <p>Hello,</p>
            <p>Thank you for subscribing to {{ config('app.name') }} newsletter!</p>
            <p>To complete your subscription, please verify your email address by clicking the button below:</p>
        </div>
        
        <div style="text-align: center;">
            <a href="{{ route('newsletter.verify', $newsletter->verification_token) }}" class="button">Verify Email Address</a>
        </div>
        
        <div class="info-box">
            <p style="margin: 0; font-size: 14px; color: #92400e;">
                <strong>⏰ Important:</strong> This verification link will expire in 7 days.
            </p>
        </div>
        
        <p style="color: #6b7280; font-size: 14px; margin-top: 20px;">
            If the button doesn't work, you can copy and paste this link into your browser:
        </p>
        <p style="word-break: break-all; color: #4f46e5; font-size: 12px;">
            {{ route('newsletter.verify', $newsletter->verification_token) }}
        </p>
        
        <p style="margin-top: 30px; color: #6b7280; font-size: 14px;">
            If you didn't subscribe to our newsletter, you can safely ignore this email.
        </p>
    </div>
    
    <div class="footer">
        <p>Thank you for joining us!</p>
        <p style="margin-top: 10px;">The {{ config('app.name') }} Team</p>
        <p style="margin-top: 15px; font-size: 12px;">
            <a href="{{ route('home') }}" style="color: #4f46e5; text-decoration: none;">Visit our website</a>
        </p>
    </div>
</body>
</html>
