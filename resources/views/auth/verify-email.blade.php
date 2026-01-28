<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email Verification</title>
</head>
<body>
    <h2>Verify your email address</h2>

    <p>
        Thanks for signing up! Before getting started, please verify your email
        address by clicking the link we just emailed to you.
    </p>

    @if (session('message'))
        <p style="color: green;">
            {{ session('message') }}
        </p>
    @endif

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit">
            Resend Verification Email
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}" style="margin-top: 10px;">
        @csrf
        <button type="submit">
            Logout
        </button>
    </form>
</body>
</html>
