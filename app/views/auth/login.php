<?php

/** @var array $data */

?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Personal Task Hub</title>

  <script nonce="<?= e(CSP_NONCE); ?>">
    (function() {
      try {
        var t = localStorage.getItem('th_theme');
        if (!t && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) t = 'dark';
        if (t === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
      } catch (e) {}
    })();
  </script>

  <link rel="stylesheet" href="<?= asset('css/style.css'); ?>">
</head>

<body class="auth-body">

  <main class="auth">
    <div class="auth__box">
      <div class="auth__head">
        <span class="brand__logo"><?= icon('layers'); ?></span>
        <h1 class="auth__title">Personal Task Hub</h1>
        <p class="auth__sub">Masuk untuk mengelola Tasks, Agenda &amp; Catatan Anda.</p>
      </div>

      <?php if ($data['error'] !== '') : ?>
        <div class="alert alert--danger" role="alert">
          <?= icon('alert'); ?>
          <span><?= e($data['error']); ?></span>
        </div>
      <?php endif; ?>

      <form method="post" action="<?= BASEURL; ?>/auth/login" autocomplete="on">
        <?= csrf_field(); ?>
        <div class="field">
          <label for="username">Username</label>
          <div class="input-icon">
            <?= icon('user'); ?>
            <input type="text" class="input" id="username" name="username" value="<?= e($data['username']); ?>"
              maxlength="50" autocomplete="username" autocapitalize="none" spellcheck="false" required autofocus>
          </div>
        </div>

        <div class="field">
          <label for="password">Password</label>
          <div class="input-icon">
            <?= icon('lock'); ?>
            <input type="password" class="input" id="password" name="password"
              autocomplete="current-password" required>
          </div>
        </div>

        <button type="submit" class="btn btn--primary auth__submit">Masuk</button>
      </form>
    </div>
  </main>

</body>

</html>