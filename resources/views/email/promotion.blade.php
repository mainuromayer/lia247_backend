@php
    $logoUrl = null;
    if ($systemSetting) {
        $logoPath = $systemSetting->getRawOriginal('logo') ?: $systemSetting->logo;
        if ($logoPath) {
            $logoUrl = filter_var($logoPath, FILTER_VALIDATE_URL) ? $logoPath : rtrim($baseUrl, '/') . '/' . ltrim($logoPath, '/');
        }
    }

    $adImageUrl = null;
    $adRawImage = $ad->getRawOriginal('image') ?: $ad->image;
    if ($adRawImage) {
        $adImageUrl = filter_var($adRawImage, FILTER_VALIDATE_URL) ? $adRawImage : rtrim($baseUrl, '/') . '/' . ltrim($adRawImage, '/');
    }

    $adVideoUrl = null;
    $adRawVideo = $ad->getRawOriginal('video') ?: $ad->video;
    if ($adRawVideo) {
        $adVideoUrl = filter_var($adRawVideo, FILTER_VALIDATE_URL) ? $adRawVideo : rtrim($baseUrl, '/') . '/' . ltrim($adRawVideo, '/');
    }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Special Promotion For You</title>
  <style>
    body {
      font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
      background-color: #f3f4f6;
      margin: 0;
      padding: 0;
      -webkit-font-smoothing: antialiased;
    }
    .wrapper {
      width: 100%;
      background-color: #f3f4f6;
      padding: 40px 20px;
      box-sizing: border-box;
    }
    .email-container {
      max-width: 600px;
      margin: 0 auto;
      background-color: #ffffff;
      border-radius: 16px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
      overflow: hidden;
    }
    .header {
      background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
      padding: 30px 20px;
      text-align: center;
    }
    .logo-img {
      max-height: 50px;
      margin-bottom: 10px;
      border-radius: 6px;
    }
    .header h1 {
      color: #ffffff;
      margin: 0;
      font-size: 24px;
      font-weight: 700;
      letter-spacing: -0.5px;
    }
    .content {
      padding: 40px 30px;
    }
    .greeting {
      font-size: 20px;
      font-weight: 700;
      color: #1f2937;
      margin-top: 0;
      margin-bottom: 15px;
    }
    .message {
      font-size: 16px;
      line-height: 1.6;
      color: #4b5563;
      margin-bottom: 25px;
    }
    .promo-media-container {
      margin: 30px 0;
      text-align: center;
      position: relative;
    }
    .promo-image {
      max-width: 100%;
      height: auto;
      border-radius: 12px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
      display: block;
      margin: 0 auto;
      transition: transform 0.3s ease;
    }
    .cta-container {
      text-align: center;
      margin: 20px 0;
    }
    .cta-button {
      display: inline-block;
      background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
      color: #ffffff !important;
      text-decoration: none;
      padding: 14px 35px;
      font-size: 16px;
      font-weight: 600;
      border-radius: 30px;
      box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
      transition: all 0.3s ease;
      margin: 5px;
    }
    .cta-button:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(79, 70, 229, 0.4);
    }
    .video-btn {
      background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
      box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3) !important;
    }
    .video-btn:hover {
      box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4) !important;
    }
    .footer {
      background-color: #f9fafb;
      padding: 30px;
      text-align: center;
      border-top: 1px solid #e5e7eb;
    }
    .footer p {
      font-size: 13px;
      color: #9ca3af;
      margin: 5px 0;
      line-height: 1.5;
    }
    .footer a {
      color: #4f46e5;
      text-decoration: none;
    }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="email-container">
      <!-- Header Section -->
      <div class="header">
        @if($logoUrl)
          <img src="{{ $logoUrl }}" alt="{{ $systemSetting->system_title ?? 'Logo' }}" class="logo-img">
        @endif
        <h1>{{ $systemSetting->system_title ?? config('app.name', 'LIA247') }}</h1>
      </div>

      <!-- Main Content -->
      <div class="content">
        <p class="greeting">Hello {{ $user->name }},</p>
        <p class="message">
          We have an exciting new update, offer, or promotion just for you! Take a look below to see what is new on our platform.
        </p>

        <!-- Promotion Image if exists -->
        @if($adImageUrl)
          <div class="promo-media-container">
            <a href="{{ $ad->url ?? ($adVideoUrl ?? '#') }}" target="_blank">
              <img src="{{ $adImageUrl }}" alt="Promotion Image" class="promo-image">
            </a>
          </div>
        @endif

        @if($adVideoUrl)
          <p class="message" style="text-align: center; font-style: italic; font-size: 14px; color: #6b7280; margin-top: 15px;">
            🎬 This promotion includes a video preview! Click the red button below to watch it.
          </p>
        @endif

        <!-- Action CTA Buttons -->
        <div class="cta-container">
          @if($ad->url)
            <a href="{{ $ad->url }}" class="cta-button" target="_blank">Explore Offer Now</a>
          @endif

          @if($adVideoUrl)
            <a href="{{ $adVideoUrl }}" class="cta-button video-btn" target="_blank">🎬 Watch Video Preview</a>
          @endif
        </div>
      </div>

      <!-- Footer Section -->
      <div class="footer">
        <p>You received this email because you are a valued customer of {{ $systemSetting->system_title ?? config('app.name', 'LIA247') }}.</p>
        <p>&copy; {{ date('Y') }} {{ $systemSetting->system_title ?? config('app.name', 'LIA247') }}. All rights reserved.</p>
        @if($systemSetting && ($systemSetting->phone_number || $systemSetting->email))
          <p style="margin-top: 10px;">
            @if($systemSetting->email)
              <a href="mailto:{{ $systemSetting->email }}">{{ $systemSetting->email }}</a>
            @endif
            @if($systemSetting->phone_number)
              | {{ $systemSetting->phone_code ?? '' }}{{ $systemSetting->phone_number }}
            @endif
          </p>
        @endif
      </div>
    </div>
  </div>
</body>
</html>