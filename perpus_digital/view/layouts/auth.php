<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= e($title) ?> — Aksaraya</title>
    <link rel="stylesheet" href="<?= e(asset('style.css')) ?>">
</head>

<body class="auth-page">
    <div class="auth-wrap">
        <section class="auth-brand"><img src="<?= e(asset('aksaraya.jpeg')) ?>" alt="Aksaraya"></section>
        <section class="auth-panel">
            <h1>Selamat Datang!</h1>
            <p class="auth-subtitle"><?= e($subtitle) ?></p>
            <?= partial('flash', ['flash' => $flash]) ?>
            <?= $content ?>
        </section>
    </div>
    <script src="<?= e(asset('app.js')) ?>"></script>
</body>

</html>
