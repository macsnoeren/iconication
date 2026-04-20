<style>
.edit-header { display:flex; align-items:center; gap:16px; margin-bottom:20px; flex-wrap:wrap; }
.edit-header h2 { margin:0; color:var(--color-primary); }
.nodes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
    gap: 18px;
    margin-bottom: 28px;
}
.node-card {
    background: white;
    border-radius: 16px;
    padding: 18px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.07);
    border-left: 5px solid var(--color-accent);
}
.node-card.root { border-left-color: var(--color-warn); }
.node-title {
    font-size: .78rem;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: var(--color-accent);
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.node-card.root .node-title { color: var(--color-warn); }
.node-title .add-link {
    margin-left: auto;
    text-transform: none;
    letter-spacing: 0;
    font-size: .8rem;
    font-weight: 700;
    background: var(--color-success);
    color: #fff;
    padding: 3px 10px;
    border-radius: 6px;
    text-decoration: none;
}
.node-title .del-link {
    text-transform: none;
    letter-spacing: 0;
    font-size: .75rem;
    font-weight: normal;
    color: var(--color-danger);
    text-decoration: none;
}
.node-title .del-link:hover { text-decoration: underline; }
.option-block {
    background: #f0f6ff;
    border-radius: 10px;
    padding: 12px 14px;
    margin-bottom: 10px;
}
.node-card.root .option-block { background: #fff8f0; }
.option-block:last-child { margin-bottom: 0; }
.opt-label-row {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 8px;
}
.opt-badge {
    background: var(--color-accent);
    color: white;
    border-radius: 5px;
    padding: 2px 7px;
    font-size: .72rem;
    font-weight: bold;
    flex-shrink: 0;
}
.node-card.root .opt-badge { background: var(--color-warn); }
.opt-badge.andere { background: var(--color-text-muted); }
.opt-badge.leaf   { background: var(--color-success); }
.opt-text-input {
    width: 100%; box-sizing: border-box;
    padding: 7px 10px; font-size: .95rem;
    border: 1px solid #ddd; border-radius: 7px;
    outline: none; background: white; margin-bottom: 6px;
}
.opt-text-input:focus { border-color: var(--color-accent); }
.img-row { display:flex; align-items:center; gap:8px; }
.img-thumb-wrap {
    flex-shrink:0; width:48px; height:48px;
    border:1px solid #ddd; border-radius:7px;
    background:#f8f4ff; display:flex; align-items:center;
    justify-content:center; overflow:hidden;
}
.img-thumb { width:38px; height:38px; object-fit:contain; }
.img-thumb-empty { color:#ccc; font-size:1.2rem; }
.img-url-input {
    flex:1; padding:7px 10px; font-size:.78rem;
    border:1px solid #ddd; border-radius:7px; outline:none;
    font-family:monospace; color:#555; background:white;
}
.img-url-input:focus { border-color: var(--color-accent); }
.opt-leaf-row {
    display: flex; align-items: center; gap: 8px;
    margin-top: 8px; font-size: .85rem; color: #555;
}
.opt-suggested {
    width:100%; box-sizing:border-box;
    padding:6px 10px; font-size:.85rem;
    border:1px solid #ddd; border-radius:7px;
    outline:none; background:white; margin-top:6px;
    font-style: italic; color: #555;
}
.opt-suggested:focus { border-color: var(--color-success); }
.action-bar {
    display:flex; gap:12px; justify-content:center;
    flex-wrap:wrap; padding:10px 0 20px;
}
.btn-save {
    padding:14px 36px; background:var(--color-success); color:white;
    border:none; border-radius:12px; font-size:1.1rem; font-weight:bold;
    cursor:pointer; transition:background .2s, transform .1s;
}
.btn-save:hover { background: var(--color-success-dk); }
.btn-save:active { transform: scale(.97); }
.saved-msg {
    display:none; background:#d5f5e3; color:#1e8449;
    border-radius:10px; padding:10px 18px; margin-bottom:16px; font-weight:bold;
}
</style>

<?php
$treeId = (int)$tree['id'];
?>

<div class="edit-header">
    <h2>
        <?= htmlspecialchars($tree['name']) ?>
        <span style="font-size:.75rem; font-weight:400; color:var(--color-text-muted); margin-left:8px;">
            <?= $tree['generation_mode'] === 'dynamic' ? '🤖 AI-boom' : '📋 Statisch' ?>
        </span>
    </h2>
    <a href="<?= BASE_URL ?>?action=admin_add_tree_node&tree=<?= $treeId ?>"
       class="btn btn--success">+ Optie toevoegen</a>
    <a href="<?= BASE_URL ?>?action=admin" class="btn">← Terug</a>
</div>

<div id="saved-msg" class="saved-msg">✓ Wijzigingen opgeslagen</div>

<?php if (empty($groups)): ?>
    <div class="card-admin">
        <p style="color:var(--color-text-muted);">
            Nog geen opties. Klik <strong>+ Optie toevoegen</strong> om te beginnen<?= $tree['generation_mode'] === 'dynamic' ? ', of start een AI-gesprek om opties te laten genereren' : '' ?>.
        </p>
    </div>
<?php else: ?>

<form method="POST" action="<?= BASE_URL ?>?action=admin_save_tree_nodes&tree=<?= $treeId ?>" id="edit-form">
<div class="nodes-grid">
<?php foreach ($groups as $key => $group):
    $parent   = $group['parent'];
    $children = $group['children'];
    $isRoot   = ($key === 'root');
    $headLabel = $isRoot ? 'Start' : ($parent['label'] ?? '—');
    $parentId  = $isRoot ? '' : (int)$parent['id'];
    $depth     = $isRoot ? 0 : (int)($parent['depth'] ?? 0) + 1;
    $optNum    = 0;
?>
<div class="node-card <?= $isRoot ? 'root' : '' ?>">
    <div class="node-title">
        <?= htmlspecialchars($headLabel) ?>
        <?php if (!$isRoot): ?>
            — diepte <?= $depth ?>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>?action=admin_add_tree_node&tree=<?= $treeId ?><?= $parentId !== '' ? '&parent='.$parentId : '' ?>"
           class="add-link">+ Optie</a>
    </div>

    <?php foreach ($children as $node):
        $optNum++;
        $nodeId   = (int)$node['id'];
        $imgUrl   = $node['image_url'] ?? '';
        $isLeaf   = (bool)$node['is_leaf'];
        $isAndere = ($node['label'] === 'Iets anders');
        $inputId  = "img_$nodeId";
    ?>
    <div class="option-block">
        <div class="opt-label-row">
            <?php if ($isLeaf): ?>
                <span class="opt-badge leaf">✓ Klaar</span>
            <?php elseif ($isAndere): ?>
                <span class="opt-badge andere">Anders</span>
            <?php else: ?>
                <span class="opt-badge"><?= $optNum ?></span>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>?action=admin_delete_tree_node&node=<?= $nodeId ?>"
               class="del-link" style="margin-left:auto;"
               onclick="return confirm('Verwijder \'<?= htmlspecialchars(addslashes($node['label'])) ?>\' en alle kinderen?')">
               Verwijderen
            </a>
        </div>

        <input type="text"
               name="nodes[<?= $nodeId ?>][label]"
               class="opt-text-input"
               value="<?= htmlspecialchars($node['label']) ?>"
               placeholder="Label..." maxlength="100" required>

        <div class="img-row">
            <div class="img-thumb-wrap" id="wrap_<?= $inputId ?>">
                <?php if ($imgUrl): ?>
                    <img src="<?= htmlspecialchars($imgUrl) ?>" class="img-thumb"
                         onerror="this.style.opacity='0.15'">
                <?php else: ?>
                    <div class="img-thumb-empty">?</div>
                <?php endif; ?>
            </div>
            <input type="text"
                   id="<?= $inputId ?>"
                   name="nodes[<?= $nodeId ?>][image_url]"
                   class="img-url-input"
                   value="<?= htmlspecialchars($imgUrl) ?>"
                   placeholder="https://..."
                   oninput="updateThumb('<?= $inputId ?>')">
            <button type="button" class="img-pick-btn" onclick="openPicker('<?= $inputId ?>')">🖼</button>
        </div>

        <?php $hasChildren = isset($groups[$nodeId]); ?>
        <?php if ($hasChildren): ?>
            <div class="opt-leaf-row" style="color:var(--color-accent); font-weight:600;">
                → Heeft vervolgopties
            </div>
            <input type="hidden" name="nodes[<?= $nodeId ?>][is_leaf]" value="0">
        <?php else: ?>
            <div class="opt-leaf-row">
                <input type="checkbox" id="leaf_<?= $nodeId ?>"
                       name="nodes[<?= $nodeId ?>][is_leaf]" value="1"
                       <?= $isLeaf ? 'checked' : '' ?>
                       onchange="toggleSuggested(<?= $nodeId ?>, this.checked)">
                <label for="leaf_<?= $nodeId ?>">★ Eindpunt (geen volgende node)</label>
            </div>
            <input type="text"
                   id="suggested_<?= $nodeId ?>"
                   name="nodes[<?= $nodeId ?>][suggested_message]"
                   class="opt-suggested"
                   value="<?= htmlspecialchars($node['suggested_message'] ?? '') ?>"
                   placeholder="Volledige bevestigingszin..."
                   style="<?= $isLeaf ? '' : 'display:none;' ?>">
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endforeach; ?>
</div>

<div class="action-bar">
    <a href="<?= BASE_URL ?>?action=admin" class="btn" style="padding:14px 28px; font-size:1rem;">← Terug</a>
    <button type="submit" class="btn-save">✓ Opslaan</button>
</div>
</form>

<?php endif; ?>

<script>
function updateThumb(inputId) {
    const input = document.getElementById(inputId);
    const wrap  = document.getElementById('wrap_' + inputId);
    const url   = input.value.trim();
    wrap.innerHTML = url
        ? '<img src="' + url + '" class="img-thumb" onerror="this.style.opacity=\'0.15\'">'
        : '<div class="img-thumb-empty">?</div>';
}

function toggleSuggested(nodeId, show) {
    const el = document.getElementById('suggested_' + nodeId);
    if (el) el.style.display = show ? '' : 'none';
}

if (window.location.hash === '#saved') {
    document.getElementById('saved-msg').style.display = 'block';
    setTimeout(() => document.getElementById('saved-msg').style.display = 'none', 3000);
    history.replaceState(null, '', window.location.pathname + window.location.search);
}
</script>

<?php include __DIR__ . '/_image_picker.php'; ?>
