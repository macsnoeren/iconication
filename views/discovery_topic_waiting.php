<div style="display:flex; flex-direction:column; align-items:center; justify-content:center; gap:24px; padding:60px 20px; text-align:center;">
    <div style="font-size:3rem;">🤖</div>
    <h2 style="color:#7d3c98; margin:0;">AI maakt keuzeboom...</h2>
    <p style="color:#666; font-size:1.1rem;">
        Onderwerp: <strong><?= htmlspecialchars($topicName) ?></strong><br>
        De AI bedenkt een communicatieboom. Dit duurt even.
    </p>
    <div style="width:220px; height:6px; background:#e0d0f0; border-radius:4px; overflow:hidden;">
        <div style="height:100%; width:40%; background:#9b59b6; border-radius:4px; animation:slide 1.5s infinite;"></div>
    </div>
    <a href="<?= BASE_URL ?>" style="color:#aaa; font-size:0.9rem; margin-top:8px;">Annuleren</a>
</div>
<style>@keyframes slide { 0%{width:10%} 50%{width:85%} 100%{width:10%} }</style>
<script>
(function poll() {
    fetch('<?= BASE_URL ?>?action=discovery_topic_status&job=<?= $jobId ?>&topic_name=<?= urlencode($topicName) ?>')
        .then(r => r.json())
        .then(d => {
            if (d.status === 'done' && d.redirect) window.location = d.redirect;
            else if (d.status === 'error') document.querySelector('h2').textContent = 'Fout: ' + (d.error || 'onbekend');
            else setTimeout(poll, 3000);
        })
        .catch(() => setTimeout(poll, 5000));
})();
</script>
