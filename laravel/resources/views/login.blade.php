<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Admin — NAMDEL ESPORT</title>
    <link rel="stylesheet" href="{{ asset('static/style.css') }}">
</head>
<body class="admin-login-page">
    <main class="login-box">
        <h1>PORTAL <span>MANAJER</span></h1>
        @if (session('error'))<p role="alert">{{ session('error') }}</p>@endif
        <form action="{{ route('login.authenticate') }}" method="POST">
            @csrf
            <label for="username">Username</label>
            <input id="username" name="username" autocomplete="username" value="{{ old('username') }}" required autofocus>
            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>
            <button type="submit" class="save-btn">MASUK</button>
        </form>
        <a href="{{ route('home') }}">← Kembali ke halaman utama</a>
    </main>
</body>
</html>
