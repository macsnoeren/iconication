<div style="max-width:440px; margin:48px auto;">
    <div class="card-admin">
        <h2 style="margin-bottom:8px; font-size:1.4rem;">Eerste admin aanmaken</h2>
        <p style="color:var(--color-text-muted); font-size:.9rem; margin-bottom:20px;">
            Er zijn nog geen gebruikers. Maak het eerste administrator-account aan.
        </p>
        <form method="POST" action="<?= BASE_URL ?>?action=setup">
            <div class="form-group">
                <label>Gebruikersnaam</label>
                <input type="text" name="username" class="form-control" required autofocus>
            </div>
            <div class="form-group">
                <label>Wachtwoord</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn--success" style="width:100%; justify-content:center; padding:12px;">Account aanmaken</button>
        </form>
    </div>
</div>
