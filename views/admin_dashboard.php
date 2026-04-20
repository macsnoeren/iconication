<?php
use App\Core\Config;
$apiKey      = Config::getApiKey();
$hasDynamic  = !empty(array_filter($trees ?? [], fn($t) => $t['generation_mode'] === 'dynamic'));
$activeTree  = array_filter($trees ?? [], fn($t) => $t['status'] === 'ready' && $t['generation_mode'] === 'dynamic');
$activeTree  = reset($activeTree) ?: null;
?>
<style>
.confirm-overlay {
    display:none; position:fixed; inset:0;
    background:rgba(0,0,0,.45); z-index:1000;
    align-items:center; justify-content:center;
}
.confirm-overlay.open { display:flex; }
.confirm-box {
    background:#fff; border-radius:18px;
    padding:32px 28px; max-width:360px; width:90%;
    box-shadow:0 8px 40px rgba(0,0,0,.18);
    text-align:center;
}
.confirm-box h3 { margin:0 0 8px; font-size:1.15rem; color:var(--color-danger); }
.confirm-box p  { margin:0 0 24px; color:#555; font-size:.95rem; }
.confirm-btns   { display:flex; gap:12px; justify-content:center; }
.confirm-btns a, .confirm-btns button {
    flex:1; padding:14px 0; font-size:1rem; font-weight:700;
    border-radius:12px; border:none; cursor:pointer; text-decoration:none;
    display:inline-block; text-align:center;
}
.confirm-yes { background:var(--color-danger); color:#fff; }
.confirm-yes:hover { filter:brightness(.9); }
.confirm-no  { background:#f0f0f0; color:#333; }
.confirm-no:hover { background:#e0e0e0; }
</style>

<div class="confirm-overlay" id="del-overlay">
    <div class="confirm-box">
        <h3>Boom verwijderen</h3>
        <p id="del-msg"></p>
        <div class="confirm-btns">
            <a id="del-yes" href="#" class="confirm-yes">Ja, verwijderen</a>
            <button class="confirm-no" onclick="document.getElementById('del-overlay').classList.remove('open')">Nee, terug</button>
        </div>
    </div>
</div>

<div class="confirm-overlay" id="rename-overlay">
    <div class="confirm-box">
        <h3 style="color:var(--color-accent);">Naam wijzigen</h3>
        <form id="rename-form" method="POST" action="<?= BASE_URL ?>?action=admin_rename_tree">
            <input type="hidden" name="tree_id" id="rename-tree-id">
            <input type="text" name="name" id="rename-input"
                   style="width:100%; box-sizing:border-box; padding:10px 14px; font-size:1rem;
                          border:1px solid #ddd; border-radius:10px; margin-bottom:20px; outline:none;"
                   maxlength="100" required>
            <div class="confirm-btns">
                <button type="submit" class="confirm-yes" style="background:var(--color-accent);">Opslaan</button>
                <button type="button" class="confirm-no" onclick="document.getElementById('rename-overlay').classList.remove('open')">Annuleren</button>
            </div>
        </form>
    </div>
</div>

<script>
function confirmDeleteTree(url, name) {
    document.getElementById('del-msg').textContent = 'Weet je zeker dat je "' + name + '" wilt verwijderen?';
    document.getElementById('del-yes').href = url;
    document.getElementById('del-overlay').classList.add('open');
}
function openRename(treeId, currentName) {
    document.getElementById('rename-tree-id').value = treeId;
    document.getElementById('rename-input').value = currentName;
    document.getElementById('rename-overlay').classList.add('open');
    setTimeout(() => document.getElementById('rename-input').focus(), 50);
}
</script>

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
                        <div style="display:flex; gap:6px; flex-wrap:wrap;">
                            <a href="<?= BASE_URL ?>?action=admin_tree_nodes&tree=<?= $tree['id'] ?>"
                               class="btn btn--accent" style="padding:5px 12px; font-size:.8rem;">Opties</a>
                            <button class="btn" style="padding:5px 12px; font-size:.8rem;"
                               onclick="openRename(<?= $tree['id'] ?>, '<?= htmlspecialchars(addslashes($tree['name'])) ?>')">
                               Hernoemen</button>
                            <?php if ($tree['generation_mode'] === 'dynamic'): ?>
                                <a href="<?= BASE_URL ?>?action=admin_save_as_static&tree=<?= $tree['id'] ?>"
                                   class="btn btn--success" style="padding:5px 12px; font-size:.8rem;"
                                   onclick="return confirm('Sla huidige AI-boom op als nieuwe statische boom?')">
                                   Sla op als statisch
                                </a>
                                <a href="<?= BASE_URL ?>?action=admin_set_tree_mode&tree=<?= $tree['id'] ?>&mode=static"
                                   class="btn" style="padding:5px 12px; font-size:.8rem;">Zet statisch</a>
                            <?php else: ?>
                                <a href="<?= BASE_URL ?>?action=admin_set_tree_mode&tree=<?= $tree['id'] ?>&mode=dynamic"
                                   class="btn btn--purple" style="padding:5px 12px; font-size:.8rem;">Zet AI</a>
                                <button class="btn btn--danger" style="padding:5px 12px; font-size:.8rem;"
                                   onclick="confirmDeleteTree('<?= BASE_URL ?>?action=admin_delete_tree&tree=<?= $tree['id'] ?>', '<?= htmlspecialchars(addslashes($tree['name'])) ?>')">
                                   Verwijderen</button>
                            <?php endif; ?>
                        </div>
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
