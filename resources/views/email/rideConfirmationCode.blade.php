<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ride Completion Confirmation Code</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f9f9f9;
      margin: 0;
      padding: 20px;
    }
    .email-container {
      max-width: 600px;
      margin: 0 auto;
      background-color: #ffffff;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    .header {
      text-align: center;
      font-size: 24px;
      font-weight: bold;
      margin-bottom: 20px;
      color: #333;
    }
    .otp {
      font-size: 20px;
      font-weight: bold;
      color: #ff6f61;
      text-align: center;
      margin: 20px 0;
    }
    .message {
      font-size: 16px;
      line-height: 1.5;
      color: #555;
    }
    .footer {
      text-align: center;
      font-size: 14px;
      color: #aaa;
      margin-top: 20px;
    }
  </style>
</head>
<body>
  <div class="email-container">
    <div class="header">Ride Completion Confirmation Code</div>
    <div class="message">
      Hello {{ $ride->user->name ?? 'Customer' }},<br><br>
      Your ride <strong>{{ $ride->ride_no }}</strong> is reaching its destination. Please provide the following confirmation code to the rideshare driver to complete the ride sharing:
    </div>
    <div class="otp">{{ $code }}</div>
    <div class="message">
      This code is valid for 30 minutes.
    </div>
    <div class="footer">
      Thank you for choosing our service.<br>
      The QUICC Team
    </div>
  </div>
</body>
</html>
