<div class="panel">
    <div class="panel__icon">✅</div>
    <h2 class="panel__title">Bericht begrepen</h2>

    <?php if (!empty($sentence)): ?>
        <div class="panel__message"><?= htmlspecialchars($sentence) ?></div>
    <?php endif; ?>

    <div class="panel__actions">
        <a href="<?= BASE_URL ?>?action=session_start" class="card card--leaf" style="min-height:120px;">
            <span class="card__icon">🔄</span>
            <span class="card__label">Nieuw gesprek</span>
        </a>
        <a href="<?= BASE_URL ?>" class="card" style="min-height:120px;">
            <span class="card__icon">🏠</span>
            <span class="card__label">Home</span>
        </a>
    </div>
</div>
