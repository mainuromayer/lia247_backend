<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Deleted Successfully | {{ $appName }}</title>
    @if(!empty($setting?->favicon))
        <link rel="icon" type="image/x-icon" href="{{ asset($setting->favicon) }}">
    @else
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at 50% 20%, rgba(16, 185, 129, 0.12) 0%, rgba(9, 21, 36, 0.98) 55%), #091524;
            padding: 24px 16px;
            color: #1e293b;
        }

        .success-card {
            width: 100%;
            max-width: 460px;
            background: #ffffff;
            border-radius: 24px;
            padding: 44px 32px 38px;
            box-shadow: 0 25px 60px -12px rgba(0, 0, 0, 0.55);
            text-align: center;
            animation: fadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(18px) scale(0.98);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .success-icon-box {
            width: 72px;
            height: 72px;
            background: #ecfdf5;
            color: #10b981;
            border-radius: 22px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            box-shadow: 0 10px 24px rgba(16, 185, 129, 0.2);
        }

        .success-title {
            font-size: 24px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 10px;
            letter-spacing: -0.5px;
        }

        .success-desc {
            font-size: 14px;
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 24px;
        }

        .info-pill-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 14px 16px;
            margin-bottom: 28px;
            text-align: left;
            font-size: 13px;
            color: #475569;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .info-pill-box svg {
            flex-shrink: 0;
            color: #0284c7;
            margin-top: 1px;
        }

        .btn-return {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 48px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #ffffff;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            text-decoration: none;
            box-shadow: 0 10px 20px -5px rgba(16, 185, 129, 0.4);
            transition: all 0.2s;
        }

        .btn-return:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 24px -5px rgba(16, 185, 129, 0.5);
        }

        .footer-note {
            margin-top: 24px;
            font-size: 12px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="success-card">
        <div class="success-icon-box">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
        </div>

        <h1 class="success-title">Account Purged Successfully</h1>
        <p class="success-desc">
            Your account and all associated personal records have been permanently removed from {{ $appName }} servers in full compliance with Apple App Store &amp; Google Play Store privacy regulations.
        </p>

        <div class="info-pill-box">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="16" x2="12" y2="12"/>
                <line x1="12" y1="8" x2="12.01" y2="8"/>
            </svg>
            <div>
                All active session tokens and device push notifications have been invalidated. If you wish to use our services again, you may register as a new user anytime via our mobile app.
            </div>
        </div>

        <a href="{{ route('account.delete.index') }}" class="btn-return">
            Back to Portal
        </a>

        <div class="footer-note">
            {{ $appName }} &bull; Account Privacy &amp; Data Erasure Center
        </div>
    </div>
</body>
</html>
