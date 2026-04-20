<div class="page-header">
    <h1>
        <?= htmlspecialchars($tree['name']) ?>
        <span style="font-size:.75rem; font-weight:400; color:var(--color-text-muted); margin-left:8px;">
            <?= $tree['generation_mode'] === 'dynamic' ? '🤖 AI-boom' : '📋 Statisch' ?>
        </span>
    </h1>
    <a href="<?= BASE_URL ?>?action=admin_add_tree_node&tree=<?= (int)$tree['id'] ?>"
       class="btn btn--success">+ Optie toevoegen</a>
    <a href="<?= BASE_URL ?>?action=admin" class="btn">← Terug</a>
</div>

<?php if (empty($groups)): ?>
    <div class="card-admin">
        <p style="color:var(--color-text-muted);">
            Nog geen opties. Klik <strong>+ Optie toevoegen</strong> om te beginnen,
            of start een AI-gesprek om opties te laten genereren.
        </p>
    </div>
<?php else: ?>

<style>
.tree-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 18px;
    margin-bottom: 24px;
}
.tree-card {
    background: var(--color-surface);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
    border: 1px solid var(--color-border);
}
.tree-card__head {
    background: var(--color-primary);
    color: #fff;
    padding: 10px 16px;
    font-size: .85rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 8px;
}
.tree-card__head .depth-badge {
    background: rgba(255,255,255,.18);
    border-radius: 20px;
    padding: 1px 8px;
    font-size: .75rem;
    font-weight: 400;
}
.tree-card__head .add-root {
    margin-left: auto;
    background: var(--color-success);
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 3px 10px;
    font-size: .8rem;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
}
.tree-option {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    border-bottom: 1px solid var(--color-border);
}
.tree-option:last-child { border-bottom: none; }
.tree-option__img {
    width: 44px; height: 44px;
    object-fit: contain;
    border-radius: 8px;
    border: 1px solid var(--color-border);
    flex-shrink: 0;
    background: #f8fafc;
}
.tree-option__img-empty {
    width: 44px; height: 44px;
    border-radius: 8px;
    border: 1px dashed var(--color-border);
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--color-text-muted);
    font-size: 1.2rem;
    background: #f8fafc;
}
.tree-option__label {
    flex: 1;
    font-weight: 600;
    font-size: .95rem;
    color: var(--color-text);
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.tree-option__label .leaf-badge {
    display: inline-block;
    background: var(--color-success);
    color: #fff;
    border-radius: 10px;
    padding: 1px 8px;
    font-size: .72rem;
    font-weight: 700;
    margin-left: 6px;
    vertical-align: middle;
}
.tree-option__label .andere-badge {
    display: inline-block;
    background: var(--color-text-muted);
    color: #fff;
    border-radius: 10px;
    padding: 1px 8px;
    font-size: .72rem;
    font-weight: 700;
    margin-left: 6px;
    vertical-align: middle;
}
.tree-option__actions {
    display: flex;
    gap: 4px;
    flex-shrink: 0;
}
.tree-option__actions a {
    padding: 4px 8px;
    border-radius: 6px;
    font-size: .78rem;
    font-weight: 700;
    text-decoration: none;
    color: #fff;
    display: inline-flex;
    align-items: center;
}
.tree-option__actions .act-add  { background: var(--color-success); }
.tree-option__actions .act-edit { background: var(--color-accent); }
.tree-option__actions .act-del  { background: var(--color-danger); }
</style>

<div class="tree-grid">
<?php foreach ($groups as $key => $group):
    $parent   = $group['parent'];
    $children = $group['children'];
    $isRoot   = ($key === 'root');
    $headLabel = $isRoot ? 'Start' : htmlspecialchars($parent['label'] ?? '—');
    $depth     = $isRoot ? 0 : (int)($parent['depth'] ?? 0);
?>
<div class="tree-card">
    <div class="tree-card__head">
        <?= $isRoot ? '🏠 ' : str_repeat('↳ ', $depth) ?>
        <?= $headLabel ?>
        <span class="depth-badge">diepte <?= $isRoot ? 0 : $depth + 1 ?></span>
        <a href="<?= BASE_URL ?>?action=admin_add_tree_node&tree=<?= (int)$tree['id'] ?><?= !$isRoot ? '&parent='.(int)$parent['id'] : '' ?>"
           class="add-root">+ Optie</a>
    </div>

    <?php foreach ($children as $node):
        $isAndere = ($node['label'] === 'Iets anders');
    ?>
    <div class="tree-option">
        <?php if (!empty($node['image_url'])): ?>
            <img class="tree-option__img"
                 src="<?= htmlspecialchars($node['image_url']) ?>"
                 alt="<?= htmlspecialchars($node['label']) ?>"
                 onerror="this.style.opacity='.2'">
        <?php else: ?>
            <div class="tree-option__img-empty"><?= $isAndere ? '↩' : '💬' ?></div>
        <?php endif; ?>

        <span class="tree-option__label">
            <?= htmlspecialchars($node['label']) ?>
            <?php if ($node['is_leaf']): ?>
                <span class="leaf-badge">✓ klaar</span>
            <?php elseif ($isAndere): ?>
                <span class="andere-badge">anders</span>
            <?php endif; ?>
        </span>

        <div class="tree-option__actions">
            <a class="act-add"
               href="<?= BASE_URL ?>?action=admin_add_tree_node&tree=<?= (int)$tree['id'] ?>&parent=<?= (int)$node['id'] ?>"
               title="Kind toevoegen">+</a>
            <a class="act-edit"
               href="<?= BASE_URL ?>?action=admin_edit_tree_node&node=<?= (int)$node['id'] ?>">✎</a>
            <a class="act-del"
               href="<?= BASE_URL ?>?action=admin_delete_tree_node&node=<?= (int)$node['id'] ?>"
               onclick="return confirm('Verwijder \'<?= htmlspecialchars(addslashes($node['label'])) ?>\' en alle kinderen?')">✕</a>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endforeach; ?>
</div>

<?php endif; ?>
