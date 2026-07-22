<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Locked</title>
</head>
<body>
    {{-- Minimal default unlock form. Consuming apps publish/override this view. --}}
    <main>
        <h1>This document is protected</h1>

        @if (! empty($error))
            <p role="alert">{{ $error }}</p>
        @endif

        <form method="POST" action="{{ route('mansio.unlock', $token) }}">
            @csrf

            @if ($challengeType === 'otp')
                <label for="otp">One-time code</label>
                <input id="otp" name="otp" type="text" inputmode="numeric" autocomplete="one-time-code" required>
            @else
                <label for="password">Password</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
            @endif

            <button type="submit">Unlock</button>
        </form>
    </main>
</body>
</html>
