<div class="page-header">
    <h1><?= $node['id'] ? 'Node bewerken' : 'Nieuwe node' ?></h1>
    <a href="<?= BASE_URL ?>?action=admin_topic_nodes&topic=<?= (int)$node['topic_id'] ?>" class="btn">← Terug</a>
</div>

<div style="max-width:720px;">
    <div class="card-admin">
        <form method="POST" action="<?= BASE_URL ?>?action=admin_save_node&topic=<?= (int)$node['topic_id'] ?><?= $node['id'] ? '&node='.$node['id'] : '' ?>">

            <div id="options-list">
                <?php foreach ($options as $i => $opt): ?>
                <div class="option-block" data-index="<?= $i ?>">
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;">
                        <strong style="color:var(--color-primary);">Optie <?= $i + 1 ?></strong>
                        <?php if ($i >= 2): ?>
                            <button type="button" class="btn btn--danger" style="padding:3px 10px; font-size:.8rem;"
                                    onclick="removeOption(this)">✕ Verwijder</button>
                        <?php endif; ?>
                    </div>
                    <input type="hidden" name="option_id[]" value="<?= htmlspecialchars((string)($opt['id'] ?? '')) ?>">
                    <div style="display:flex; gap:12px; flex-wrap:wrap;">
                        <div style="flex:2; min-width:160px;">
                            <label class="form-group" style="margin-bottom:4px; font-size:.85rem; font-weight:600;">Label</label>
                            <input type="text" name="option_label[]" class="form-control"
                                   value="<?= htmlspecialchars($opt['label'] ?? '') ?>" required>
                        </div>
                        <div style="flex:3; min-width:180px;">
                            <label style="display:block; margin-bottom:4px; font-size:.85rem; font-weight:600;">Afbeelding URL</label>
                            <input type="text" name="option_image[]" class="form-control"
                                   value="<?= htmlspecialchars($opt['image_url'] ?? '') ?>">
                        </div>
                        <div style="flex:1; min-width:100px;">
                            <label style="display:block; margin-bottom:4px; font-size:.85rem; font-weight:600;">Volgende node ID</label>
                            <input type="number" name="option_next_node[]" class="form-control"
                                   value="<?= htmlspecialchars((string)($opt['next_node_id'] ?? '')) ?>">
                        </div>
                    </div>
                </div>
                <hr style="border:none; border-top:1px solid var(--color-border); margin:14px 0;">
                <?php endforeach; ?>
            </div>

            <button type="button" class="btn btn--accent" style="margin-bottom:16px;" onclick="addOption()">
                + Optie toevoegen
            </button>

            <div style="display:flex; gap:10px;">
                <button type="submit" class="btn btn--success" style="flex:1; justify-content:center; padding:12px;">Opslaan</button>
                <a href="<?= BASE_URL ?>?action=admin_topic_nodes&topic=<?= (int)$node['topic_id'] ?>"
                   class="btn" style="padding:12px 20px;">Annuleren</a>
            </div>
        </form>
    </div>
</div>

<script>
let optionCount = <?= count($options) ?>;

function addOption() {
    const list = document.getElementById('options-list');
    const idx  = optionCount++;
    const div  = document.createElement('div');
    div.className = 'option-block';
    div.innerHTML = `
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
            <strong style="color:var(--color-primary);">Optie ${idx + 1}</strong>
            <button type="button" class="btn btn--danger" style="padding:3px 10px;font-size:.8rem;"
                    onclick="removeOption(this)">✕ Verwijder</button>
        </div>
        <input type="hidden" name="option_id[]" value="">
        <div style="display:flex;gap:12px;flex-wrap:wrap;">
            <div style="flex:2;min-width:160px;">
                <label style="display:block;margin-bottom:4px;font-size:.85rem;font-weight:600;">Label</label>
                <input type="text" name="option_label[]" class="form-control" required>
            </div>
            <div style="flex:3;min-width:180px;">
                <label style="display:block;margin-bottom:4px;font-size:.85rem;font-weight:600;">Afbeelding URL</label>
                <input type="text" name="option_image[]" class="form-control">
            </div>
            <div style="flex:1;min-width:100px;">
                <label style="display:block;margin-bottom:4px;font-size:.85rem;font-weight:600;">Volgende node ID</label>
                <input type="number" name="option_next_node[]" class="form-control">
            </div>
        </div>
        <hr style="border:none;border-top:1px solid var(--color-border);margin:14px 0;">`;
    list.appendChild(div);
}

function removeOption(btn) {
    btn.closest('.option-block').nextElementSibling?.remove(); // remove <hr>
    btn.closest('.option-block').remove();
}
</script>
