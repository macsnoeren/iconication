<div class="panel">
    <div class="panel__icon spinner">🤖</div>
    <p style="font-size:1.2rem; color:var(--color-text-muted);">AI denkt na...</p>
    <?php if (!empty($sentence)): ?>
        <p style="color:var(--color-text-muted); font-size:.95rem;"><?= htmlspecialchars($sentence) ?></p>
    <?php endif; ?>
</div>

<script>
(function poll() {
    fetch('<?= BASE_URL ?>?action=session_dynamic_status&job=<?= (int)$jobId ?>')
        .then(r => r.json())
        .then(data => {
            if (data.status === 'done')   { window.location.href = data.redirect; return; }
            if (data.status === 'failed') { window.location.href = '<?= BASE_URL ?>'; return; }
            setTimeout(poll, 1500);
        })
        .catch(() => setTimeout(poll, 2000));
})();
</script>
