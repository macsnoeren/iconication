<?php
use App\Core\Config;
$apiKey = Config::getApiKey();
?>

<!-- AI Worker sectie -->
<details style="background:white; border-radius:12px; padding:16px 20px; margin-bottom:24px; border:1px solid #e8d5f5;">
    <summary style="cursor:pointer; font-weight:bold; color:#7d3c98; font-size:1rem; list-style:none; display:flex; align-items:center; gap:8px;">
        🔑 AI Worker configuratie
        <span style="font-size:0.8rem; font-weight:normal; color:#aaa; margin-left:auto;">klik om te openen</span>
    </summary>
    <div style="margin-top:16px; display:flex; flex-direction:column; gap:10px;">
        <div style="font-size:0.9rem; color:#555;">
            Kopieer de API key naar <code>ai_service/.env</code> als <code>ICONICATION_API_KEY</code>.
            Stel ook <code>PHP_APP_URL</code> in en voer daarna de worker uit:
        </div>
        <div style="display:flex; gap:8px; align-items:center;">
            <code id="api-key-val" style="flex:1; background:#f4f0ff; border:1px solid #d7b8f7; border-radius:8px; padding:10px 14px; font-size:0.85rem; word-break:break-all;"><?= htmlspecialchars($apiKey) ?></code>
            <button onclick="navigator.clipboard.writeText(document.getElementById('api-key-val').textContent).then(()=>{this.textContent='✓ Gekopieerd';setTimeout(()=>this.textContent='📋 Kopieer',1500)})"
                style="padding:10px 14px; background:#9b59b6; color:white; border:none; border-radius:8px; cursor:pointer; white-space:nowrap;">📋 Kopieer</button>
        </div>
        <div style="background:#f8f8f8; border-radius:8px; padding:12px 16px; font-size:0.82rem; color:#444; font-family:monospace;">
            cd ai_service<br>
            pip install -r requirements.txt<br>
            cp config.ini.example config.ini &nbsp;<span style="color:#888"># vul keys in</span><br>
            python worker.py &nbsp;<span style="color:#888"># blijft draaien, polt elke 10s</span>
        </div>
    </div>
</details>

<h2>Beheer Onderwerpen</h2>
<div style="display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap;">
    <a href="<?= BASE_URL ?>?action=admin_add_topic" class="btn" style="background:#2ecc71;">+ Nieuw Onderwerp</a>
    <a href="<?= BASE_URL ?>?action=admin_ai_generate" class="btn" style="background:#9b59b6;">🤖 AI Genereer Onderwerp</a>
    <a href="<?= BASE_URL ?>?action=admin_training" class="btn" style="background:#e67e22;">🎓 AI Trainen</a>
</div>
<table style="width: 100%; background: white; border-collapse: collapse; border-radius: 10px; overflow: hidden;">
    <thead style="background: #2c3e50; color: white;">
        <tr>
            <th style="padding: 15px; text-align: left;">Naam</th>
            <th style="padding: 15px; text-align: center;">Acties</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($topics as $topic): ?>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 15px;"><?= htmlspecialchars($topic['name']) ?></td>
                <td style="padding: 15px; text-align: center; display: flex; gap: 10px; justify-content: center;">
                    <a href="<?= BASE_URL ?>?action=admin_topic_edit&topic=<?= $topic['id'] ?>" class="btn" style="background:#3498db; padding: 8px 15px;">Bewerken</a>
                    <a href="<?= BASE_URL ?>?topic=<?= $topic['id'] ?>&action=admin_topic_delete" style="color: red;" onclick="return confirm('Zeker weten?')">Verwijderen</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>