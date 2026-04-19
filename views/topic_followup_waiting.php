<div style="display:flex; flex-direction:column; align-items:center; justify-content:center; gap:24px; padding:60px 20px; text-align:center;">
    <div style="font-size:3rem;" id="spin">🤖</div>
    <h2 style="color:#7d3c98; margin:0;">AI maakt vervolg...</h2>
    <p style="color:#666; font-size:1.1rem;" id="status-msg">Eén moment, de AI bedenkt een vervolggesprek.</p>
    <div style="width:200px; height:6px; background:#e0d0f0; border-radius:4px; overflow:hidden;">
        <div id="progress-bar" style="height:100%; width:30%; background:#9b59b6; border-radius:4px; animation:slide 1.5s infinite;"></div>
    </div>
    <a href="<?= BASE_URL ?>?topic=<?= $topicId ?>" style="color:#aaa; font-size:0.9rem; margin-top:8px;">Annuleren</a>
</div>

<style>
@keyframes slide { 0%{width:10%} 50%{width:80%} 100%{width:10%} }
</style>

<script>
(function poll() {
    fetch('<?= BASE_URL ?>?action=topic_followup_status&job=<?= $jobId ?>&topic=<?= $topicId ?>')
        .then(r => r.json())
        .then(data => {
            if (data.status === 'done' && data.redirect) {
                window.location = data.redirect;
            } else if (data.status === 'error') {
                document.getElementById('status-msg').textContent = 'Fout: ' + (data.error || 'onbekend');
                document.getElementById('spin').textContent = '❌';
            } else {
                setTimeout(poll, 3000);
            }
        })
        .catch(() => setTimeout(poll, 5000));
})();
</script>
