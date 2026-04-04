<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Iconication - AAC</title>
    <style>
        :root { --primary: #2c3e50; --bg: #f4f7f6; --accent: #3498db; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: var(--bg); margin: 0; display: flex; flex-direction: column; height: 100vh; overflow: hidden; }
        .header { background: var(--primary); color: white; padding: 1rem; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .container { flex: 1; padding: 20px; display: flex; flex-direction: column; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; flex: 1; }
        .card { background: white; border: 8px solid #ddd; border-radius: 30px; display: flex; flex-direction: column; 
                align-items: center; justify-content: center; text-decoration: none; color: #333; transition: transform 0.1s; }
        .card:active { transform: scale(0.96); border-color: var(--accent); }
        .card img { max-width: 65%; max-height: 60%; object-fit: contain; }
        .card span { font-size: 2.5rem; font-weight: bold; margin-top: 15px; }
        .btn { padding: 12px 24px; background: #555; color: white; border-radius: 12px; text-decoration: none; font-weight: bold; }
        .topic-list { display: grid; grid-template-columns: 1fr; gap: 15px; }
        .topic-btn { background: white; padding: 30px; border-radius: 20px; text-align: center; 
                     text-decoration: none; color: var(--primary); font-size: 2rem; font-weight: bold; border: 4px solid #ccc; }
    </style>
</head>
<body>
    <div class="header">
        <?php if($view !== 'home'): ?>
            <a href="<?= BASE_URL ?>?topic=<?= $topicId ?>&action=back" class="btn">⬅ Terug</a>
        <?php endif; ?>
        <div style="font-size: 1.5rem;">Iconication 
            <?php if(isset($_SESSION['username'])): ?>
                <span style="font-size: 0.8rem;">(Welkom <?= htmlspecialchars($_SESSION['username']) ?>)</span>
            <?php endif; ?>
        </div>
        <div>
            <a href="<?= BASE_URL ?>" class="btn">🏠 Home</a>
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="<?= BASE_URL ?>?action=change_password" class="btn" style="background:#9b59b6">🔑 Wachtwoord</a>
                <?php if($_SESSION['role'] === 'admin'): ?>
                    <a href="<?= BASE_URL ?>?action=admin" class="btn" style="background:#2ecc71">⚙ Admin</a>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>?action=logout" class="btn" style="background:#e74c3c">Uitloggen</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>?action=login" class="btn">Inloggen</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="container">
        <?php include __DIR__ . "/$view.php"; ?>
    </div>
</body>
</html>