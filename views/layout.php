<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Iconication</title>
    <link rel="stylesheet" href="<?= rtrim(dirname(BASE_URL), '/') ?>/style.css">
</head>
<?php
$fullscreen = in_array($view ?? '', ['session', 'session_waiting', 'home']);
$noBack     = in_array($view ?? '', ['session', 'session_waiting', 'session_complete', 'session_guess', 'home'])
           || str_starts_with($view ?? '', 'admin_');
?>
<body<?= $fullscreen ? ' class="fullscreen"' : '' ?>>

<header class="header">
    <?php if (!$noBack): ?>
        <?php if (($view ?? '') === 'session_confirm'): ?>
            <form method="POST" action="<?= BASE_URL ?>?action=session_back">
                <button type="submit" class="btn btn--ghost">← Terug</button>
            </form>
        <?php else: ?>
            <a href="<?= BASE_URL ?>" class="btn btn--ghost">← Terug</a>
        <?php endif; ?>
    <?php endif; ?>

    <span class="header__title">
        Iconication
        <?php if (isset($_SESSION['username'])): ?>
            <span class="header__sub">(<?= htmlspecialchars($_SESSION['username']) ?>)</span>
        <?php endif; ?>
    </span>

    <div class="header__actions">
        <a href="<?= BASE_URL ?>" class="btn btn--ghost">🏠</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="<?= BASE_URL ?>?action=change_password" class="btn btn--ghost">🔑</a>
            <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                <a href="<?= BASE_URL ?>?action=admin" class="btn btn--success">⚙ Admin</a>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>?action=logout" class="btn btn--danger">Uitloggen</a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>?action=login" class="btn btn--ghost">Inloggen</a>
        <?php endif; ?>
    </div>
</header>

<div class="container">
    <?php include __DIR__ . "/$view.php"; ?>
</div>

</body>
</html>
