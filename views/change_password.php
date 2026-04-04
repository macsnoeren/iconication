<div style="max-width: 400px; margin: 50px auto; background: white; padding: 20px; border-radius: 15px; border: 1px solid #ddd;">
    <h2>Wachtwoord Wijzigen</h2>
    <?php if (isset($error)): ?>
        <p style="color: red;"><?= $error ?></p>
    <?php endif; ?>
    <?php if (isset($success)): ?>
        <p style="color: green;"><?= $success ?></p>
    <?php endif; ?>
    <form method="POST" action="<?= BASE_URL ?>?action=change_password">
        <input type="password" name="current_password" placeholder="Huidig wachtwoord" required style="width:100%; padding:10px; margin-bottom:10px;"><br>
        <input type="password" name="new_password" placeholder="Nieuw wachtwoord" required style="width:100%; padding:10px; margin-bottom:10px;"><br>
        <input type="password" name="confirm_password" placeholder="Bevestig nieuw wachtwoord" required style="width:100%; padding:10px; margin-bottom:10px;"><br>
        <button type="submit" class="btn" style="width:100%; border:none; cursor:pointer;">Wachtwoord Opslaan</button>
    </form>
</div>