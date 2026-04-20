<?php $total = ($dynamicTree ? 1 : 0) + count($staticTrees); ?>

<div style="display:flex; flex-direction:column; flex:1; min-height:0; gap:10px;">

    <div style="display:flex; align-items:center; padding:10px 0 2px;">
        <span class="sentence-bar" style="font-size:1.1rem; flex:1;">Waar wil je over praten?</span>
        <?php if ($dynamicTree): ?>
            <a href="<?= BASE_URL ?>?action=session_reset_dynamic"
               class="btn btn--danger" style="font-size:.8rem; padding:6px 12px;"
               onclick="return confirm('Reset alle AI-opties en begin opnieuw?')">↺ Reset AI</a>
        <?php endif; ?>
    </div>

    <div class="card-grid<?= $total >= 3 ? ' card-grid--4' : '' ?>" style="flex:1; min-height:0;">

        <?php if ($dynamicTree): ?>
            <a href="<?= BASE_URL ?>?action=session_start&tree=<?= (int)$dynamicTree['id'] ?>"
               class="card card--leaf">
                <span class="card__icon">🤖</span>
                <span class="card__label">Start een gesprek</span>
            </a>
        <?php endif; ?>

        <?php foreach ($staticTrees as $tree): ?>
            <a href="<?= BASE_URL ?>?action=session_start&tree=<?= (int)$tree['id'] ?>"
               class="card">
                <span class="card__icon">💬</span>
                <span class="card__label"><?= htmlspecialchars($tree['name']) ?></span>
            </a>
        <?php endforeach; ?>

    </div>

</div>
