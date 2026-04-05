<style>
.preview-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 12px;
}
.preview-header h2 { margin: 0; color: #2c3e50; font-size: 1.6rem; }
.topic-name-row {
    background: white;
    border-radius: 14px;
    padding: 20px 24px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
}
.topic-name-row label { font-weight: bold; color: #555; white-space: nowrap; }
.topic-name-row input {
    flex: 1;
    padding: 10px 16px;
    font-size: 1.1rem;
    border: 2px solid #ddd;
    border-radius: 10px;
    outline: none;
}
.topic-name-row input:focus { border-color: #9b59b6; }
.nodes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}
.node-card {
    background: white;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.07);
    border-left: 5px solid #9b59b6;
}
.node-card.root { border-left-color: #e67e22; }
.node-title {
    font-size: 0.8rem;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #9b59b6;
    margin-bottom: 14px;
}
.node-card.root .node-title { color: #e67e22; }
.option-row {
    background: #f8f4ff;
    border-radius: 10px;
    padding: 12px 14px;
    margin-bottom: 10px;
}
.option-row:last-child { margin-bottom: 0; }
.option-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
}
.option-badge {
    background: #9b59b6;
    color: white;
    border-radius: 6px;
    padding: 2px 8px;
    font-size: 0.75rem;
    font-weight: bold;
}
.hint-badge {
    background: #e8f4fd;
    color: #2980b9;
    border: 1px solid #bee3f8;
    border-radius: 12px;
    padding: 2px 10px;
    font-size: 0.78rem;
}
.arrow-badge {
    margin-left: auto;
    font-size: 0.78rem;
    color: #888;
}
.arrow-badge.terminal {
    color: #27ae60;
    font-weight: bold;
}
.option-row input[type="text"] {
    width: 100%;
    box-sizing: border-box;
    padding: 8px 12px;
    font-size: 1rem;
    border: 1px solid #ddd;
    border-radius: 8px;
    outline: none;
    background: white;
}
.option-row input[type="text"]:focus { border-color: #9b59b6; }
.img-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
}
.img-thumb-wrap {
    flex-shrink: 0;
    width: 56px;
    height: 56px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: #f8f4ff;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}
.img-thumb {
    width: 44px;
    height: 44px;
    object-fit: contain;
}
.img-thumb-empty {
    color: #ccc;
    font-size: 1.4rem;
}
.img-url-input {
    flex: 1;
    padding: 8px 12px;
    font-size: 0.82rem;
    border: 1px solid #ddd;
    border-radius: 8px;
    outline: none;
    background: white;
    font-family: monospace;
    color: #555;
}
.img-url-input:focus { border-color: #9b59b6; }
.preview-select {
    width: 100%;
    padding: 8px 12px;
    font-size: 0.9rem;
    border: 1px solid #ddd;
    border-radius: 8px;
    outline: none;
    background: white;
    margin-bottom: 8px;
    color: #333;
}
.preview-select:focus { border-color: #9b59b6; }
.action-bar {
    display: flex;
    gap: 14px;
    justify-content: center;
    flex-wrap: wrap;
    padding: 10px 0 20px;
}
.btn-accept {
    padding: 16px 36px;
    background: #27ae60;
    color: white;
    border: none;
    border-radius: 14px;
    font-size: 1.15rem;
    font-weight: bold;
    cursor: pointer;
    transition: background 0.2s, transform 0.1s;
}
.btn-accept:hover { background: #219a52; }
.btn-accept:active { transform: scale(0.97); }
.btn-cancel {
    padding: 16px 36px;
    background: #e74c3c;
    color: white;
    border-radius: 14px;
    font-size: 1.15rem;
    font-weight: bold;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    transition: background 0.2s;
}
.btn-cancel:hover { background: #c0392b; }
.legend {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
    font-size: 0.85rem;
    color: #666;
    margin-bottom: 20px;
}
.legend span { display: flex; align-items: center; gap: 6px; }
</style>

<?php if (!empty($ai_goal)): ?>
<div style="background:#f0e8ff; border:1px solid #d7b8f7; border-radius:12px; padding:14px 18px; margin-bottom:20px; font-size:0.95rem; color:#5b2c6f;">
    <strong>Doel:</strong> <?= htmlspecialchars($ai_goal) ?>
</div>
<?php endif; ?>

<div class="preview-header">
    <h2>🌳 Voorvertoning Decision Tree</h2>
    <div class="legend">
        <span><span style="display:inline-block;width:12px;height:12px;background:#e67e22;border-radius:3px;"></span> Startnode</span>
        <span><span style="display:inline-block;width:12px;height:12px;background:#9b59b6;border-radius:3px;"></span> Tussennode</span>
        <span style="color:#27ae60;">★ Eindpunt</span>
        <span style="color:#2980b9;">💡 Afbeelding-hint</span>
    </div>
</div>

<form method="POST" action="<?= BASE_URL ?>?action=admin_ai_save" id="save-form">
    <?php if (!empty($job['id'])): ?>
        <input type="hidden" name="job_id" value="<?= (int)$job['id'] ?>">
    <?php endif; ?>
    <div class="topic-name-row">
        <label for="topic_name">Onderwerpnaam:</label>
        <input
            type="text"
            id="topic_name"
            name="topic_name"
            value="<?= htmlspecialchars($tree['topic'] ?? '') ?>"
            maxlength="200"
            required
        >
    </div>

    <?php
    $nodes   = $tree['nodes'] ?? [];
    $nodeIds = array_column($nodes, 'id');

    // Verzamel alle AI-gevonden image_urls zodat de picker ze toont
    $aiImageUrls = [];
    foreach ($nodes as $n) {
        foreach (['option_a', 'option_b'] as $k) {
            $u = $n[$k]['image_url'] ?? '';
            if ($u) $aiImageUrls[] = $u;
        }
    }
    $aiImageUrls = array_values(array_unique($aiImageUrls));
    ?>

    <div class="nodes-grid">
        <?php foreach ($nodes as $i => $node):
            $isRoot   = ($node['id'] === 1);
            $cardClass = $isRoot ? 'node-card root' : 'node-card';
            $titleText = $isRoot
                ? 'Node ' . $node['id'] . ' — Start'
                : 'Node ' . $node['id'];
        ?>
        <div class="<?= $cardClass ?>">
            <div class="node-title"><?= $titleText ?></div>

            <?php foreach (['option_a' => 'A', 'option_b' => 'B'] as $optKey => $optLabel):
                $opt        = $node[$optKey];
                $nextId     = $opt['next_node_id'] ?? null;
                $imageUrl   = $opt['image_url'] ?? '';
                $isTerminal = ($nextId === null || !in_array($nextId, $nodeIds));
                $arrowText  = $isTerminal ? '★ Eindpunt' : '→ Node ' . $nextId;
                $arrowClass = $isTerminal ? 'arrow-badge terminal' : 'arrow-badge';
                $inputId    = "img_{$i}_{$optKey}";
            ?>
            <div class="option-row">
                <div class="option-header">
                    <span class="option-badge"><?= $optLabel ?></span>
                    <?php if (!empty($opt['image_hint'])): ?>
                        <span class="hint-badge">💡 <?= htmlspecialchars($opt['image_hint']) ?></span>
                    <?php endif; ?>
                </div>
                <input type="hidden" name="nodes[<?= $i ?>][id]"                              value="<?= $node['id'] ?>">
                <input type="hidden" name="nodes[<?= $i ?>][<?= $optKey ?>][image_hint]" value="<?= htmlspecialchars($opt['image_hint'] ?? '') ?>">
                <select name="nodes[<?= $i ?>][<?= $optKey ?>][next_node_id]" class="preview-select">
                    <option value="">★ Eindpunt (geen volgende node)</option>
                    <?php foreach ($nodes as $targetNode):
                        $tid = (int)$targetNode['id'];
                        if ($tid === (int)$node['id']) continue;
                    ?>
                        <option value="<?= $tid ?>" <?= ((int)$nextId === $tid) ? 'selected' : '' ?>>
                            Node <?= $tid ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- Afbeelding preview + URL -->
                <div class="img-row">
                    <div class="img-thumb-wrap">
                        <?php if ($imageUrl): ?>
                            <img src="<?= htmlspecialchars($imageUrl) ?>"
                                 class="img-thumb"
                                 id="thumb_<?= $inputId ?>"
                                 onerror="this.style.opacity='0.2'">
                        <?php else: ?>
                            <div class="img-thumb-empty">?</div>
                        <?php endif; ?>
                    </div>
                    <input
                        type="text"
                        id="<?= $inputId ?>"
                        name="nodes[<?= $i ?>][<?= $optKey ?>][image_url]"
                        value="<?= htmlspecialchars($imageUrl) ?>"
                        placeholder="https://..."
                        class="img-url-input"
                        oninput="updateThumb('<?= $inputId ?>')"
                    >
                    <button type="button" class="img-pick-btn" onclick="openPicker('<?= $inputId ?>')">🖼</button>
                </div>

                <!-- Label -->
                <input
                    type="text"
                    name="nodes[<?= $i ?>][<?= $optKey ?>][label]"
                    value="<?= htmlspecialchars($opt['label'] ?? '') ?>"
                    maxlength="100"
                    placeholder="Label..."
                    required
                >
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="action-bar">
        <a href="<?= BASE_URL ?>?action=admin_ai_generate" class="btn-cancel">✕ Annuleren</a>
        <button type="submit" class="btn-accept">✓ Accepteren &amp; Opslaan</button>
    </div>
</form>

<script>
const pickerExtraImages = <?= json_encode($aiImageUrls) ?>;

function updateThumb(inputId) {
    const input = document.getElementById(inputId);
    const wrap  = input.closest('.img-row').querySelector('.img-thumb-wrap');
    const url   = input.value.trim();
    if (!url) {
        wrap.innerHTML = '<div class="img-thumb-empty">?</div>';
        return;
    }
    wrap.innerHTML = '<img src="' + url + '" class="img-thumb" onerror="this.style.opacity=\'0.2\'">';
}
</script>

<?php include __DIR__ . '/_image_picker.php'; ?>
