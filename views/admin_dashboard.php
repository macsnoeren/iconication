<?php
use App\Core\Config;
$apiKey      = Config::getApiKey();
$hasDynamic  = !empty(array_filter($trees ?? [], fn($t) => $t['generation_mode'] === 'dynamic'));
$activeTree  = array_filter($trees ?? [], fn($t) => $t['status'] === 'ready' && $t['generation_mode'] === 'dynamic');
$activeTree  = reset($activeTree) ?: null;
?>

<!-- AI gespreks-modus -->
<div class="card-admin" style="margin-bottom:20px;">
    <div style="display:flex; align-items:center; gap:16px; flex-wrap:wrap;">
        <div style="flex:1;">
            <div style="font-weight:700; font-size:1.05rem; margin-bottom:4px;">
                Gespreks-modus
            </div>
            <?php if ($activeTree): ?>
                <div style="color:var(--color-success); font-size:.9rem;">
                    ✅ <strong>AI actief</strong> — elke stap worden opties real-time gegenereerd
                </div>
            <?php else: ?>
                <div style="color:var(--color-text-muted); font-size:.9rem;">
                    📋 <strong>Statisch</strong> — vooraf gegenereerde onderwerpen worden getoond
                </div>
            <?php endif; ?>
        </div>
        <?php if ($activeTree): ?>
            <a href="<?= BASE_URL ?>?action=admin_enable_static" class="btn"
               onclick="return confirm('Zet terug naar statische modus?')">
                Zet statisch
            </a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>?action=admin_enable_dynamic" class="btn btn--accent">
                🤖 Zet AI-modus aan
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Per-boom modus instelling -->
<?php if (!empty($trees)): ?>
<div class="card-admin" style="margin-bottom:20px;">
    <div style="font-weight:700; margin-bottom:12px;">Actieve bomen</div>
    <table class="table">
        <thead>
            <tr>
                <th>Naam</th>
                <th>Status</th>
                <th>Modus</th>
                <th style="width:180px;">Wisselaar</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($trees as $tree): ?>
                <tr>
                    <td><?= htmlspecialchars($tree['name']) ?></td>
                    <td><?= $tree['status'] === 'ready' ? '✅ Klaar' : '⏳ ' . htmlspecialchars($tree['status']) ?></td>
                    <td>
                        <?php if ($tree['generation_mode'] === 'dynamic'): ?>
                            <span style="color:var(--color-accent); font-weight:600;">🤖 AI</span>
                        <?php else: ?>
                            <span style="color:var(--color-text-muted);">📋 Statisch</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($tree['generation_mode'] === 'dynamic'): ?>
                            <a href="<?= BASE_URL ?>?action=admin_set_tree_mode&tree=<?= $tree['id'] ?>&mode=static"
                               class="btn" style="padding:5px 12px; font-size:.8rem;">Zet statisch</a>
                        <?php else: ?>
                            <a href="<?= BASE_URL ?>?action=admin_set_tree_mode&tree=<?= $tree['id'] ?>&mode=dynamic"
                               class="btn btn--accent" style="padding:5px 12px; font-size:.8rem;">Zet AI</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- AI Worker configuratie -->
<details class="card-admin" style="margin-bottom:20px;">
    <summary style="cursor:pointer; font-weight:700; color:var(--color-purple); list-style:none; display:flex; align-items:center; gap:8px;">
        🔑 AI Worker configuratie
        <span style="font-size:.8rem; font-weight:400; color:var(--color-text-muted); margin-left:auto;">klik om te openen</span>
    </summary>
    <div style="margin-top:16px; display:flex; flex-direction:column; gap:10px;">
        <p style="font-size:.9rem; color:var(--color-text-muted);">
            Kopieer de API key naar <code>ai_service/config.ini</code> als <code>api_key</code>.
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
python worker.py</pre>
    </div>
</details>

<!-- Onderwerpen tabel -->
<div class="page-header">
    <h1>Onderwerpen (statisch)</h1>
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
