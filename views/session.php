<?php $count = count($options); ?>

<div style="display:flex; flex-direction:column; flex:1; min-height:0; gap:10px;">

    <?php if ($sentence): ?>
        <div class="sentence-bar"><?= htmlspecialchars($sentence) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>?action=session_select" id="sel-form"
          style="flex:1; min-height:0; display:flex; flex-direction:column;">
        <input type="hidden" name="node_id" id="node-input" value="">

        <div class="card-grid<?= $count >= 3 ? ' card-grid--4' : '' ?>" style="flex:1; min-height:0;">
            <?php foreach ($options as $node): ?>
                <button type="button"
                        class="card<?= $node['is_leaf'] ? ' card--leaf' : '' ?>"
                        onclick="document.getElementById('node-input').value='<?= (int)$node['id'] ?>';
                                 document.getElementById('sel-form').submit();">
                    <?php if (!empty($node['image_url'])): ?>
                        <img class="card__image"
                             src="<?= htmlspecialchars($node['image_url']) ?>"
                             alt="<?= htmlspecialchars($node['label']) ?>">
                    <?php else: ?>
                        <span class="card__icon">💬</span>
                    <?php endif; ?>
                    <span class="card__label"><?= htmlspecialchars($node['label']) ?></span>
                    <?php if ($node['is_leaf']): ?>
                        <span class="card__badge">✓ Klaar</span>
                    <?php endif; ?>
                </button>
            <?php endforeach; ?>
        </div>
    </form>

</div>
