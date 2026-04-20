<div class="panel">
    <div class="panel__icon">⚠️</div>
    <h2 class="panel__title">Er ging iets mis</h2>
    <div style="background:#fdecea; border:1px solid #f5c6c2; border-radius:var(--radius-md);
                padding:14px 20px; max-width:520px; font-size:.9rem; color:#922b21; word-break:break-word;">
        <?= htmlspecialchars($message ?? 'Onbekende fout') ?>
    </div>
    <a href="<?= BASE_URL ?>" class="btn btn--accent" style="margin-top:8px;">← Terug naar home</a>
</div>
