<div style="display:flex; flex-direction:column; align-items:center; justify-content:center;
            flex:1; min-height:0; gap:20px; text-align:center; padding:20px;">

    <?php if (!empty($sentence)): ?>
    <div style="background:#f0e8fc; border-left:4px solid #9b59b6; border-radius:0 10px 10px 0;
                padding:8px 14px; font-size:1rem; color:#5b2c6f; align-self:stretch; flex-shrink:0;">
        💬 <?= htmlspecialchars($sentence) ?>
    </div>
    <?php endif; ?>

    <div style="font-size:3rem; animation:pulse 1.5s ease-in-out infinite;">🤖</div>
    <p style="color:#7d3c98; font-size:1.2rem; font-weight:bold; margin:0;">AI denkt na...</p>
    <div style="width:200px; height:6px; background:#e0d0f0; border-radius:4px; overflow:hidden;">
        <div style="height:100%; background:#9b59b6; border-radius:4px;
                    animation:slide 1.6s ease-in-out infinite;"></div>
    </div>
    <p style="color:#aaa; font-size:0.85rem; margin:0;">Even wachten</p>
</div>
<style>
@keyframes pulse { 0%,100%{transform:scale(1)} 50%{transform:scale(1.15)} }
@keyframes slide { 0%{width:8%;margin-left:0} 50%{width:60%;margin-left:20%} 100%{width:8%;margin-left:92%} }
</style>
<script>
(function poll() {
    fetch('<?= BASE_URL ?>?action=dynamic_status&job=<?= $jobId ?>')
        .then(r => r.json())
        .then(d => {
            if (d.status === 'done' && d.redirect) {
                window.location = d.redirect;
            } else if (d.status === 'error') {
                document.querySelector('p').textContent = 'Fout: ' + (d.error || 'onbekend');
            } else {
                setTimeout(poll, 2500);
            }
        })
        .catch(() => setTimeout(poll, 4000));
})();
</script>
