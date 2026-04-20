<div class="page-header">
    <h1>Nieuwe optie toevoegen</h1>
    <a href="<?= BASE_URL ?>?action=admin_tree_nodes&tree=<?= (int)$tree['id'] ?>" class="btn">← Terug</a>
</div>

<div style="max-width:600px;">
    <div class="card-admin">
        <?php if ($parentLabel): ?>
            <p style="color:var(--color-text-muted); margin-bottom:16px; font-size:.9rem;">
                Kind van: <strong><?= htmlspecialchars($parentLabel) ?></strong>
            </p>
        <?php else: ?>
            <p style="color:var(--color-text-muted); margin-bottom:16px; font-size:.9rem;">
                Toevoegen als <strong>root optie</strong> (geen ouder)
            </p>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>?action=admin_save_new_tree_node">
            <input type="hidden" name="tree_id"   value="<?= (int)$tree['id'] ?>">
            <input type="hidden" name="parent_id" value="<?= $parentId ?? '' ?>">

            <div class="form-group">
                <label>Label</label>
                <input type="text" name="label" class="form-control" required maxlength="100" autofocus>
            </div>

            <div class="form-group">
                <label>Afbeelding URL</label>
                <div style="display:flex; gap:10px; align-items:flex-start;">
                    <div style="flex:1;">
                        <input type="url" name="image_url" id="image_url" class="form-control"
                               oninput="updatePreview(this.value)">
                    </div>
                    <div id="img-preview" style="height:80px; width:80px; border:1px dashed var(--color-border); border-radius:8px; display:flex; align-items:center; justify-content:center; color:var(--color-text-muted); font-size:.75rem;">geen</div>
                </div>
            </div>

            <div class="form-group">
                <label>Bevestigingszin <span style="font-weight:400; color:var(--color-text-muted)">(alleen voor eindopties)</span></label>
                <input type="text" name="suggested_message" class="form-control" maxlength="500"
                       placeholder="bijv. Ik wil graag buiten wandelen">
            </div>

            <div class="form-group" style="display:flex; align-items:center; gap:10px;">
                <input type="checkbox" name="is_leaf" id="is_leaf" value="1">
                <label for="is_leaf" style="margin:0; font-weight:400;">Dit is een eindpunt (blad)</label>
            </div>

            <div style="display:flex; gap:10px; margin-top:8px;">
                <button type="submit" class="btn btn--success" style="flex:1; justify-content:center; padding:12px;">Toevoegen</button>
                <a href="<?= BASE_URL ?>?action=admin_tree_nodes&tree=<?= (int)$tree['id'] ?>"
                   class="btn" style="padding:12px 20px;">Annuleren</a>
            </div>
        </form>
    </div>
</div>

<script>
function updatePreview(url) {
    const el = document.getElementById('img-preview');
    if (url) {
        el.outerHTML = '<img id="img-preview" src="' + url + '" style="height:80px;width:80px;object-fit:contain;border:1px solid var(--color-border);border-radius:8px;" onerror="this.src=\'\'">';
    }
}
</script>
