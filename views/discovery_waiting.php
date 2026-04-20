<div style="display:flex; flex-direction:column; align-items:center; justify-content:center;
            gap:24px; height:70vh; text-align:center; padding:20px;">
    <div style="font-size:4rem;">🤖</div>
    <h2 style="color:#7d3c98; margin:0; font-size:1.8rem;">Een moment...</h2>
    <p style="color:#666; font-size:1.1rem; max-width:340px;">
        De AI bedenkt de beste manier om te ontdekken wat je wilt zeggen.
    </p>
    <div style="width:260px; height:8px; background:#e0d0f0; border-radius:4px; overflow:hidden;">
        <div style="height:100%; background:#9b59b6; border-radius:4px; animation:slide 1.8s ease-in-out infinite;"></div>
    </div>
</div>
<style>
@keyframes slide {
    0%   { width: 8%; margin-left: 0; }
    50%  { width: 60%; margin-left: 20%; }
    100% { width: 8%; margin-left: 92%; }
}
</style>
<script>
(function poll() {
    fetch('<?= BASE_URL ?>?action=discovery_waiting_status&job=<?= $jobId ?>')
        .then(r => r.json())
        .then(d => {
            if (d.status === 'done' && d.redirect) window.location = d.redirect;
            else if (d.status === 'error') {
                document.querySelector('h2').textContent = 'Kon niet starten.';
                document.querySelector('p').textContent  = 'Vraag een begeleider om hulp.';
            } else {
                setTimeout(poll, 3000);
            }
        })
        .catch(() => setTimeout(poll, 5000));
})();
</script>
