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

<?php if (empty($nodes)): ?>
    <div class="card-admin">
        <p style="color:var(--color-text-muted);">Nog geen opties gegenereerd. Start een gesprek om de AI-opties te laten genereren.</p>
    </div>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th style="width:40px;">Diepte</th>
                <th>Label</th>
                <th>Afbeelding</th>
                <th>Ouder</th>
                <th style="width:60px;">Blad</th>
                <th style="width:160px; text-align:center;">Acties</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($nodes as $node): ?>
                <tr>
                    <td style="color:var(--color-text-muted); text-align:center;">
                        <?= str_repeat('↳ ', $node['depth']) ?><?= $node['depth'] ?>
                    </td>
                    <td>
                        <strong><?= htmlspecialchars($node['label']) ?></strong>
                        <?php if ($node['suggested_message']): ?>
                            <div style="font-size:.8rem; color:var(--color-text-muted); margin-top:2px;">
                                "<?= htmlspecialchars($node['suggested_message']) ?>"
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($node['image_url']): ?>
                            <img src="<?= htmlspecialchars($node['image_url']) ?>"
                                 alt="<?= htmlspecialchars($node['label']) ?>"
                                 style="height:48px; width:48px; object-fit:contain; border-radius:6px; border:1px solid var(--color-border);">
                        <?php else: ?>
                            <span style="color:var(--color-text-muted); font-size:.85rem;">geen</span>
                        <?php endif; ?>
                    </td>
                    <td style="color:var(--color-text-muted); font-size:.9rem;">
                        <?= $node['parent_label'] ? htmlspecialchars($node['parent_label']) : '—' ?>
                    </td>
                    <td style="text-align:center;">
                        <?= $node['is_leaf'] ? '✅' : '' ?>
                    </td>
                    <td style="text-align:center;">
                        <div style="display:flex; gap:6px; justify-content:center; flex-wrap:wrap;">
                            <a href="<?= BASE_URL ?>?action=admin_add_tree_node&tree=<?= (int)$tree['id'] ?>&parent=<?= $node['id'] ?>"
                               class="btn btn--success" style="padding:5px 10px; font-size:.8rem;">+ Kind</a>
                            <a href="<?= BASE_URL ?>?action=admin_edit_tree_node&node=<?= $node['id'] ?>"
                               class="btn btn--accent" style="padding:5px 12px; font-size:.8rem;">Bewerken</a>
                            <a href="<?= BASE_URL ?>?action=admin_delete_tree_node&node=<?= $node['id'] ?>"
                               class="btn btn--danger" style="padding:5px 12px; font-size:.8rem;"
                               onclick="return confirm('Verwijder deze optie en alle kinderen?')">✕</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
