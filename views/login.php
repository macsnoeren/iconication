<div style="max-width: 400px; margin: 50px auto; background: white; padding: 20px; border-radius: 15px; border: 1px solid #ddd;">
    <h2>Inloggen</h2>
    <?php if (isset($error)): ?>
        <p style="color: red;"><?= $error ?></p>
    <?php endif; ?>
    <form method="POST" action="<?= BASE_URL ?>?action=login">
        <input type="text" name="username" placeholder="Gebruikersnaam" required style="width:100%; padding:10px; margin-bottom:10px;"><br>
        <input type="password" name="password" placeholder="Wachtwoord" required style="width:100%; padding:10px; margin-bottom:10px;"><br>
        <button type="submit" class="btn" style="width:100%; border:none; cursor:pointer;">Inloggen</button>
    </form>
</div>