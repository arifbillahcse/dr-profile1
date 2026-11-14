<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Backend setup | {{ config('app.name') }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; background: #eef7f4; color: #163c2d; font: 16px/1.7 system-ui, sans-serif; }
        main { max-width: 640px; padding: clamp(24px, 5vw, 56px); background: #fff; border-top: 5px solid #ffb703; border-radius: 20px; box-shadow: 0 20px 70px #123d2d12; }
        .label { color: #188454; font-size: 13px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
        h1 { margin: 12px 0; font-size: clamp(28px, 5vw, 40px); line-height: 1.2; }
        p { color: #52655d; }
        a { color: #146c43; font-weight: 600; }
        a:focus-visible { outline: 3px solid #ffb703; outline-offset: 5px; }
    </style>
</head>
<body>
    <main>
        <div class="label">{{ config('app.name') }}</div>
        <h1>Backend foundation ready</h1>
        <p>The administration area is ready. Sign in to view your dashboard and account settings.</p>
        <a href="{{ route('admin.login') }}">Admin sign in</a>
    </main>
</body>
</html>
