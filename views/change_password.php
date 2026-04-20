<div style="max-width:400px; margin:48px auto;">
    <div class="card-admin">
        <h2 style="margin-bottom:20px; font-size:1.4rem;">Wachtwoord wijzigen</h2>
        <?php if (isset($error)): ?>
            <div class="alert alert--error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <div class="alert alert--success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <form method="POST" action="<?= BASE_URL ?>?action=change_password">
            <div class="form-group">
                <label>Huidig wachtwoord</label>
                <input type="password" name="current_password" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Nieuw wachtwoord</label>
                <input type="password" name="new_password" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Bevestig nieuw wachtwoord</label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn--accent" style="width:100%; justify-content:center; padding:12px;">Opslaan</button>
        </form>
    </div>
</div>
