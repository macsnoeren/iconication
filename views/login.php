<div style="max-width:400px; margin:48px auto;">
    <div class="card-admin">
        <h2 style="margin-bottom:20px; font-size:1.4rem;">Inloggen</h2>
        <?php if (isset($error)): ?>
            <div class="alert alert--error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" action="<?= BASE_URL ?>?action=login">
            <div class="form-group">
                <label>Gebruikersnaam</label>
                <input type="text" name="username" class="form-control" required autofocus>
            </div>
            <div class="form-group">
                <label>Wachtwoord</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn--accent" style="width:100%; justify-content:center; padding:12px;">Inloggen</button>
        </form>
    </div>
</div>
