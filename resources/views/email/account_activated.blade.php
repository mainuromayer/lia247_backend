<!DOCTYPE html>
<html>

{{-- <head>
    <title>Account Activated</title>
</head> --}}

<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;">
        <h2 style="color: #4CAF50;">Congratulations, {{ $user->name }}!</h2>
        <p>Your account on <strong>{{ config('app.name') }}</strong> has been successfully activated by the
            administrator.</p>
        <p>You can now log in to your account using your email: <strong>{{ $user->email }}</strong>.</p>
        {{-- <div style="margin: 30px 0; text-align: center;">
            <a href="{{ url('/login') }}"
                style="background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Login
                Now</a>
        </div> --}}
        <p>Thank you for being with us!</p>


        <br>
        <p>Best Regards,<br>The {{ config('app.name') }} Team</p>
    </div>
</body>

</html>