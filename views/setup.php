<div style="max-width: 400px; margin: 50px auto; background: white; padding: 20px; border-radius: 15px; border: 1px solid #ddd;">
    <h2>Eerste Admin Account Aanmaken</h2>
    <p>Er zijn nog geen gebruikers in de database. Maak hier het eerste administrator account aan om de applicatie te beheren.</p>
    <form method="POST" action="<?= BASE_URL ?>?action=setup">
        <input type="text" name="username" placeholder="Gebruikersnaam" required style="width:100%; padding:10px; margin-bottom:10px;"><br>
        <input type="password" name="password" placeholder="Wachtwoord" required style="width:100%; padding:10px; margin-bottom:10px;"><br>
        <button type="submit" class="btn" style="width:100%; border:none; cursor:pointer;">Account Aanmaken</button>
    </form>
</div>