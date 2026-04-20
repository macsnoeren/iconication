<div style="display:flex; flex-direction:column; flex:1; min-height:0; gap:12px;">

    <div class="sentence-bar" style="font-size:1.1rem; padding:12px 0 4px;">
        Waar wil je over praten?
    </div>

    <div class="card-grid<?= count($trees) >= 3 ? ' card-grid--4' : '' ?>" style="flex:1; min-height:0;">
        <?php foreach ($trees as $tree): ?>
            <a href="<?= BASE_URL ?>?action=session_start&tree=<?= (int)$tree['id'] ?>"
               class="card">
                <span class="card__icon">💬</span>
                <span class="card__label"><?= htmlspecialchars($tree['name']) ?></span>
            </a>
        <?php endforeach; ?>
    </div>

</div>
