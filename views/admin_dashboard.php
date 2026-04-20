<?php use App\Core\Config; $apiKey = Config::getApiKey(); ?>

<details class="card-admin" style="margin-bottom:20px;">
    <summary style="cursor:pointer; font-weight:700; color:var(--color-purple); list-style:none; display:flex; align-items:center; gap:8px;">
        🔑 AI Worker configuratie
        <span style="font-size:.8rem; font-weight:400; color:var(--color-text-muted); margin-left:auto;">klik om te openen</span>
    </summary>
    <div style="margin-top:16px; display:flex; flex-direction:column; gap:10px;">
        <p style="font-size:.9rem; color:var(--color-text-muted);">
            Kopieer de API key naar <code>ai_service/config.ini</code> als <code>ICONICATION_API_KEY</code>.
        </p>
        <div style="display:flex; gap:8px; align-items:center;">
            <code id="api-key-val" style="flex:1; background:#f4f0ff; border:1px solid #d7b8f7; border-radius:var(--radius-sm); padding:10px 14px; font-size:.85rem; word-break:break-all;">
                <?= htmlspecialchars($apiKey) ?>
            </code>
            <button onclick="navigator.clipboard.writeText(document.getElementById('api-key-val').textContent.trim()).then(()=>{this.textContent='✓ Gekopieerd';setTimeout(()=>this.textContent='📋 Kopieer',1500)})"
                    class="btn btn--purple">📋 Kopieer</button>
        </div>
        <pre style="background:#f8f8f8; border-radius:var(--radius-sm); padding:12px 16px; font-size:.82rem; color:#444; overflow-x:auto;">cd ai_service
pip install -r requirements.txt
cp config.ini.example config.ini   # vul keys in
python worker.py                   # blijft draaien</pre>
    </div>
</details>

<div class="page-header">
    <h1>Onderwerpen</h1>
    <a href="<?= BASE_URL ?>?action=admin_add_topic" class="btn btn--success">+ Nieuw</a>
    <a href="<?= BASE_URL ?>?action=admin_ai_generate" class="btn btn--purple">🤖 AI genereer</a>
    <a href="<?= BASE_URL ?>?action=admin_training" class="btn btn--warn">🎓 AI trainen</a>
</div>

<table class="table">
    <thead>
        <tr>
            <th>Naam</th>
            <th style="text-align:center; width:220px;">Acties</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($topics as $topic): ?>
            <tr>
                <td><?= htmlspecialchars($topic['name']) ?></td>
                <td style="text-align:center;">
                    <div style="display:flex; gap:8px; justify-content:center;">
                        <a href="<?= BASE_URL ?>?action=admin_topic_edit&topic=<?= $topic['id'] ?>" class="btn btn--accent" style="padding:6px 14px; font-size:.85rem;">Bewerken</a>
                        <a href="<?= BASE_URL ?>?topic=<?= $topic['id'] ?>&action=admin_topic_delete"
                           class="btn btn--danger" style="padding:6px 14px; font-size:.85rem;"
                           onclick="return confirm('Zeker weten?')">Verwijderen</a>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
