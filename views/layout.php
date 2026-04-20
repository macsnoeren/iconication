<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Iconication - AAC</title>
    <style>
        :root { --primary: #2c3e50; --bg: #f4f7f6; --accent: #3498db; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: var(--bg); margin: 0; display: flex; flex-direction: column; min-height: 100vh; }
        .header { background: var(--primary); color: white; padding: 1rem; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); flex-shrink: 0; flex-wrap: wrap; gap: 8px; }
        .container { flex: 1; padding: 20px; display: flex; flex-direction: column; }
        /* Alleen het navigatiescherm (node.php / home.php) vult de volledige viewport zonder scrollen */
        body.fullscreen { height: 100vh; overflow: hidden; }
        body.fullscreen .container { overflow: hidden; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; flex: 1; min-height: 0; }
        .grid--4 { grid-template-rows: 1fr 1fr; }
        .card { background: white; border: 8px solid #ddd; border-radius: 24px; display: flex; flex-direction: column;
                align-items: center; justify-content: center; text-decoration: none; color: #333; transition: transform 0.1s;
                overflow: hidden; min-height: 0; padding: 8px; }
        .card:active { transform: scale(0.96); border-color: var(--accent); }
        .card img { max-width: 60%; max-height: 50%; object-fit: contain; flex-shrink: 0; }
        .card span { font-size: 2.5rem; font-weight: bold; margin-top: 10px; text-align: center; line-height: 1.2; }
        .card--sm span { font-size: 1.5rem; margin-top: 6px; }
        .card--end { border-color: #27ae60; }
        .card--end:active { border-color: #1e8449; }
        .end-badge { margin-top: 8px; background: #27ae60; color: white; border-radius: 20px; padding: 4px 16px; font-size: 1.1rem; font-weight: bold; }
        .btn { padding: 12px 24px; background: #555; color: white; border-radius: 12px; text-decoration: none; font-weight: bold; }
        .topic-list { display: grid; grid-template-columns: 1fr; gap: 15px; }
        .topic-btn { background: white; padding: 30px; border-radius: 20px; text-align: center; 
                     text-decoration: none; color: var(--primary); font-size: 2rem; font-weight: bold; border: 4px solid #ccc; }
    </style>
</head>
<?php
$fullscreenViews = ['node', 'session', 'session_waiting', 'followup_node', 'discovery_node', 'dynamic_options', 'dynamic_waiting'];
$noBackViews     = ['session', 'session_waiting', 'session_complete', 'discovery_node', 'discovery_waiting',
                    'discovery_confirm', 'dynamic_options', 'dynamic_waiting', 'home'];
?>
<body<?= in_array($view ?? '', $fullscreenViews) ? ' class="fullscreen"' : '' ?>>
    <div class="header">
        <?php if (!in_array($view ?? '', $noBackViews)): ?>
            <?php if (($view ?? '') === 'session_confirm'): ?>
                <form method="POST" action="<?= BASE_URL ?>?action=session_back" style="margin:0;">
                    <button type="submit" class="btn">⬅ Terug</button>
                </form>
            <?php elseif (($topicId ?? 0) > 0): ?>
                <a href="<?= BASE_URL ?>?topic=<?= $topicId ?>&action=back" class="btn">⬅ Terug</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>" class="btn">⬅ Terug</a>
            <?php endif; ?>
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