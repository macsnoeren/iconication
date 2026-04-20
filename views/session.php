<div style="display:flex; flex-direction:column; flex:1; min-height:0; gap:12px;">

    <?php if ($sentence): ?>
        <div style="text-align:center; color:#666; font-size:1.05rem; padding:4px 0; flex-shrink:0;">
            <?= htmlspecialchars($sentence) ?>
        </div>
    <?php endif; ?>

    <?php
        $count      = count($options);
        $gridClass  = 'grid' . ($count >= 3 ? ' grid--4' : '');
    ?>

    <form method="POST" action="<?= BASE_URL ?>?action=session_select" id="sel-form" style="flex:1; min-height:0; display:flex; flex-direction:column;">
        <input type="hidden" name="node_id" id="node-input" value="">

        <div class="<?= $gridClass ?>" style="flex:1; min-height:0;">
            <?php foreach ($options as $node): ?>
                <button type="button"
                        class="card<?= $node['is_leaf'] ? ' card--end' : '' ?>"
                        onclick="document.getElementById('node-input').value='<?= (int)$node['id'] ?>';
                                 document.getElementById('sel-form').submit();">
                    <?php if (!empty($node['image_url'])): ?>
                        <img src="<?= htmlspecialchars($node['image_url']) ?>"
                             alt="<?= htmlspecialchars($node['label']) ?>">
                    <?php else: ?>
                        <span style="font-size:3rem;">💬</span>
                    <?php endif; ?>
                    <span><?= htmlspecialchars($node['label']) ?></span>
                    <?php if ($node['is_leaf']): ?>
                        <span class="end-badge">✓ Klaar</span>
                    <?php endif; ?>
                </button>
            <?php endforeach; ?>
        </div>
    </form>

</div>
