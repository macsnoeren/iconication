<style>
.edit-header {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}
.edit-header h2 { margin: 0; color: #2c3e50; }
.topic-meta {
    background: white;
    border-radius: 14px;
    padding: 16px 20px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.topic-meta label { font-weight: bold; color: #555; white-space: nowrap; }
.topic-meta input[type="text"] {
    flex: 1;
    min-width: 180px;
    padding: 9px 14px;
    font-size: 1rem;
    border: 2px solid #ddd;
    border-radius: 10px;
    outline: none;
}
.topic-meta input[type="text"]:focus { border-color: #3498db; }
.topic-meta input[type="number"] {
    width: 90px;
    padding: 9px 14px;
    font-size: 1rem;
    border: 2px solid #ddd;
    border-radius: 10px;
    outline: none;
}
.topic-meta input[type="number"]:focus { border-color: #3498db; }
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
    border-left: 5px solid #3498db;
}
.node-card.root { border-left-color: #e67e22; }
.node-title {
    font-size: 0.78rem;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #3498db;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.node-card.root .node-title { color: #e67e22; }
.node-title a {
    margin-left: auto;
    font-size: 0.75rem;
    font-weight: normal;
    text-transform: none;
    letter-spacing: 0;
    color: #e74c3c;
    text-decoration: none;
}
.node-title a:hover { text-decoration: underline; }
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
    background: #3498db;
    color: white;
    border-radius: 5px;
    padding: 2px 7px;
    font-size: 0.72rem;
    font-weight: bold;
    flex-shrink: 0;
}
.node-card.root .opt-badge { background: #e67e22; }
.arrow-badge { font-size: 0.75rem; color: #888; margin-left: auto; white-space: nowrap; }
.arrow-badge.terminal { color: #27ae60; font-weight: bold; }
.opt-text-input {
    width: 100%;
    box-sizing: border-box;
    padding: 7px 10px;
    font-size: 0.95rem;
    border: 1px solid #ddd;
    border-radius: 7px;
    outline: none;
    background: white;
    margin-bottom: 6px;
}
.opt-text-input:focus { border-color: #3498db; }
.img-row {
    display: flex;
    align-items: center;
    gap: 8px;
}
.img-thumb-wrap {
    flex-shrink: 0;
    width: 48px; height: 48px;
    border: 1px solid #ddd;
    border-radius: 7px;
    background: #f8f4ff;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}
.img-thumb { width: 38px; height: 38px; object-fit: contain; }
.img-thumb-empty { color: #ccc; font-size: 1.2rem; }
.img-url-input {
    flex: 1;
    padding: 7px 10px;
    font-size: 0.78rem;
    border: 1px solid #ddd;
    border-radius: 7px;
    outline: none;
    font-family: monospace;
    color: #555;
    background: white;
}
.img-url-input:focus { border-color: #3498db; }
.opt-select {
    width: 100%;
    padding: 7px 10px;
    font-size: 0.9rem;
    border: 1px solid #ddd;
    border-radius: 7px;
    outline: none;
    background: white;
    margin-bottom: 6px;
    color: #333;
}
.opt-select:focus { border-color: #3498db; }
.action-bar {
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
    padding: 10px 0 20px;
}
.btn-save {
    padding: 14px 36px;
    background: #27ae60;
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 1.1rem;
    font-weight: bold;
    cursor: pointer;
    transition: background 0.2s, transform 0.1s;
}
.btn-save:hover { background: #219a52; }
.btn-save:active { transform: scale(0.97); }
.btn-back {
    padding: 14px 36px;
    background: #95a5a6;
    color: white;
    border-radius: 12px;
    font-size: 1.1rem;
    font-weight: bold;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    transition: background 0.2s;
}
.btn-back:hover { background: #7f8c8d; }
.btn-add-node {
    padding: 14px 28px;
    background: #2ecc71;
    color: white;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: bold;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    transition: background 0.2s;
}
.btn-add-node:hover { background: #27ae60; }
.saved-msg {
    display: none;
    background: #d5f5e3;
    color: #1e8449;
    border-radius: 10px;
    padding: 10px 18px;
    margin-bottom: 16px;
    font-weight: bold;
}
</style>

<?php
$allNodeIds = array_column(array_column($nodes, 'node'), 'id');
?>

<div class="edit-header">
    <h2>Bewerken: <?= htmlspecialchars($topic['name']) ?></h2>
    <a href="<?= BASE_URL ?>?action=admin_add_node&topic=<?= $topic['id'] ?>" class="btn-add-node">+ Node toevoegen</a>
</div>

<div id="saved-msg" class="saved-msg">✓ Wijzigingen opgeslagen</div>

<form method="POST" action="<?= BASE_URL ?>?action=admin_save_topic_edit&topic=<?= $topic['id'] ?>" id="edit-form">

    <div class="topic-meta">
        <label for="topic_name">Naam:</label>
        <input type="text" id="topic_name" name="topic_name"
               value="<?= htmlspecialchars($topic['name']) ?>" maxlength="200" required>
        <label for="root_node_id">Root node:</label>
        <input type="number" id="root_node_id" name="root_node_id"
               value="<?= htmlspecialchars((string)($topic['root_node_id'] ?? '')) ?>" min="1">
    </div>

    <div class="nodes-grid">
        <?php foreach ($nodes as $i => $entry):
            $node    = $entry['node'];
            $options = $entry['options'];
            $isRoot  = ((int)$node['id'] === (int)$topic['root_node_id']);
        ?>
        <div class="node-card <?= $isRoot ? 'root' : '' ?>">
            <div class="node-title">
                Node <?= $node['id'] ?><?= $isRoot ? ' — Start' : '' ?>
                <a href="<?= BASE_URL ?>?action=admin_delete_node&topic=<?= $topic['id'] ?>&node=<?= $node['id'] ?>"
                   onclick="return confirm('Node <?= $node['id'] ?> en bijbehorende opties verwijderen?')">Verwijderen</a>
            </div>

            <?php foreach (['option_a' => 'A', 'option_b' => 'B'] as $optKey => $optLabel):
                $opt      = $options[$optKey === 'option_a' ? 0 : 1];
                $optId    = $opt['id'] ?? null;
                $nextId   = $opt['next_node_id'] ?? null;
                $imgUrl   = $opt['image_url'] ?? '';
                $isTerm   = ($nextId === null || !in_array((int)$nextId, $allNodeIds));
                $arrowTxt = $isTerm ? '★ Einde' : '→ Node ' . $nextId;
                $inputId  = "img_{$i}_{$optKey}";
            ?>
            <div class="option-block">
                <input type="hidden" name="nodes[<?= $i ?>][<?= $optKey ?>][id]" value="<?= (int)$optId ?>">
                <div class="opt-label-row">
                    <span class="opt-badge"><?= $optLabel ?></span>
                </div>
                <input type="text"
                       class="opt-text-input"
                       name="nodes[<?= $i ?>][<?= $optKey ?>][label]"
                       value="<?= htmlspecialchars($opt['label'] ?? '') ?>"
                       placeholder="Label..." maxlength="100" required>
                <input type="hidden"
                       name="nodes[<?= $i ?>][<?= $optKey ?>][image_hint]"
                       value="<?= htmlspecialchars($opt['image_hint'] ?? '') ?>">
                <select name="nodes[<?= $i ?>][<?= $optKey ?>][next_node_id]" class="opt-select">
                    <option value="">★ Eindpunt (geen volgende node)</option>
                    <?php foreach ($nodes as $targetEntry):
                        $tid = (int)$targetEntry['node']['id'];
                        if ($tid === (int)$node['id']) continue; // niet naar zichzelf
                    ?>
                        <option value="<?= $tid ?>" <?= ((int)$nextId === $tid) ? 'selected' : '' ?>>
                            Node <?= $tid ?>
                        </option>
                    <?php endforeach; ?>
                </select>
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
                           name="nodes[<?= $i ?>][<?= $optKey ?>][image_url]"
                           class="img-url-input"
                           value="<?= htmlspecialchars($imgUrl) ?>"
                           placeholder="https://..."
                           oninput="updateThumb('<?= $inputId ?>')">
                    <button type="button" class="img-pick-btn" onclick="openPicker('<?= $inputId ?>')">🖼</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="action-bar">
        <a href="<?= BASE_URL ?>?action=admin" class="btn-back">← Onderwerpen</a>
        <button type="submit" class="btn-save">✓ Opslaan</button>
    </div>
</form>

<script>
function updateThumb(inputId) {
    const input = document.getElementById(inputId);
    const wrap  = document.getElementById('wrap_' + inputId);
    const url   = input.value.trim();
    if (!url) {
        wrap.innerHTML = '<div class="img-thumb-empty">?</div>';
    } else {
        wrap.innerHTML = '<img src="' + url + '" class="img-thumb" onerror="this.style.opacity=\'0.15\'">';
    }
}
</script>

<?php include __DIR__ . '/_image_picker.php'; ?>

<script>

// Toon opgeslagen-melding als er een anchor #saved in de URL staat
if (window.location.hash === '#saved') {
    const msg = document.getElementById('saved-msg');
    msg.style.display = 'block';
    setTimeout(() => msg.style.display = 'none', 3000);
    history.replaceState(null, '', window.location.pathname + window.location.search);
}
</script>
