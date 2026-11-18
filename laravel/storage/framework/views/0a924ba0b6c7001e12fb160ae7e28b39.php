<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Admin — NAMDEL ESPORT</title>
    <link rel="stylesheet" href="<?php echo e(asset('static/style.css')); ?>">
</head>
<body class="admin-login-page">
    <main class="login-box">
        <h1>PORTAL <span>MANAJER</span></h1>
        <?php if(session('error')): ?><p role="alert"><?php echo e(session('error')); ?></p><?php endif; ?>
        <form action="<?php echo e(route('login.authenticate')); ?>" method="POST">
            <?php echo csrf_field(); ?>
            <label for="username">Username</label>
            <input id="username" name="username" autocomplete="username" value="<?php echo e(old('username')); ?>" required autofocus>
            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>
            <button type="submit" class="save-btn">MASUK</button>
        </form>
        <a href="<?php echo e(route('home')); ?>">← Kembali ke halaman utama</a>
    </main>
</body>
</html>
<?php /**PATH C:\Users\aimar\OneDrive\Dokumen\webb esport mlbb sma68\laravel\resources\views\login.blade.php ENDPATH**/ ?>