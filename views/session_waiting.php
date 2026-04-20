<div class="panel" id="wait-panel">
    <div class="panel__icon spinner">🤖</div>
    <p style="font-size:1.2rem; color:var(--color-text-muted);" id="wait-msg">AI denkt na...</p>
    <?php if (!empty($sentence)): ?>
        <p style="color:var(--color-text-muted); font-size:.95rem;"><?= htmlspecialchars($sentence) ?></p>
    <?php endif; ?>
    <p id="wait-sub" style="color:var(--color-text-muted); font-size:.8rem; display:none;">
        Dit kan 10–30 seconden duren.
    </p>
</div>

<script>
var attempts = 0;
(function poll() {
    attempts++;
    if (attempts === 5)  document.getElementById('wait-sub').style.display = '';
    if (attempts === 40) document.getElementById('wait-msg').textContent = 'Nog even geduld...';

    fetch('<?= BASE_URL ?>?action=session_dynamic_status&job=<?= (int)$jobId ?>')
        .then(r => r.json())
        .then(data => {
            if (data.status === 'done')   { window.location.href = data.redirect; return; }
            if (data.status === 'error' || data.status === 'failed') {
                window.location.href = '<?= BASE_URL ?>';
                return;
            }
            setTimeout(poll, 1500);
        })
        .catch(() => setTimeout(poll, 2000));
})();
</script>
