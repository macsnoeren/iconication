<style>
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }
.spinner { animation: pulse 1.4s ease-in-out infinite; font-size:3.5rem; }
</style>

<div style="display:flex; flex-direction:column; align-items:center; justify-content:center;
            flex:1; gap:24px; text-align:center; padding:40px 24px;">

    <div class="spinner">🤖</div>
    <div style="font-size:1.3rem; color:#555;">AI denkt na...</div>

    <?php if (!empty($sentence)): ?>
        <div style="color:#888; font-size:1rem;">
            <?= htmlspecialchars($sentence) ?>
        </div>
    <?php endif; ?>

</div>

<script>
(function poll() {
    fetch('<?= BASE_URL ?>?action=session_dynamic_status&job=<?= (int)$jobId ?>')
        .then(r => r.json())
        .then(data => {
            if (data.status === 'done')    { window.location.href = data.redirect; return; }
            if (data.status === 'failed')  { window.location.href = '<?= BASE_URL ?>'; return; }
            setTimeout(poll, 1500);
        })
        .catch(() => setTimeout(poll, 2000));
})();
</script>
