<style>
.waiting-card {
    max-width: 500px;
    margin: 60px auto;
    background: white;
    border-radius: 20px;
    padding: 48px 40px;
    text-align: center;
    box-shadow: 0 4px 24px rgba(0,0,0,0.09);
}
.waiting-icon {
    font-size: 3rem;
    margin-bottom: 16px;
}
.waiting-card h2 {
    margin: 0 0 8px;
    color: #2c3e50;
}
.waiting-card .topic-label {
    color: #9b59b6;
    font-size: 1.1rem;
    font-weight: bold;
    margin-bottom: 6px;
}
.waiting-card .goal-label {
    color: #888;
    font-size: 0.9rem;
    margin-bottom: 32px;
}
.pulse-ring {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: #f0e8ff;
    margin-bottom: 24px;
    position: relative;
}
.pulse-ring::after {
    content: '';
    position: absolute;
    width: 80px;
    height: 80px;
    border-radius: 50%;
    border: 3px solid #9b59b6;
    animation: pulse 1.5s ease-out infinite;
}
@keyframes pulse {
    0%   { transform: scale(1);   opacity: 0.8; }
    100% { transform: scale(1.6); opacity: 0; }
}
.spinner-large {
    width: 36px; height: 36px;
    border: 4px solid #e8d5f5;
    border-top-color: #9b59b6;
    border-radius: 50%;
    animation: spin 0.9s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
.status-text {
    color: #888;
    font-size: 0.9rem;
    margin-top: 20px;
}
.btn-cancel-sm {
    display: inline-block;
    margin-top: 24px;
    padding: 10px 24px;
    background: #eee;
    color: #555;
    border-radius: 10px;
    text-decoration: none;
    font-size: 0.9rem;
    transition: background 0.2s;
}
.btn-cancel-sm:hover { background: #ddd; }
</style>

<div class="waiting-card">
    <div class="pulse-ring">
        <div class="spinner-large"></div>
    </div>
    <h2>Wachten op de worker...</h2>
    <div class="topic-label"><?= htmlspecialchars($job['topic']) ?></div>
    <?php if (!empty($job['goal'])): ?>
        <div class="goal-label"><?= htmlspecialchars($job['goal']) ?></div>
    <?php endif; ?>
    <div class="status-text" id="status-msg">
        De Python worker moet nog verwerken.<br>
        Start de worker zodat de job opgepakt wordt.
    </div>
    <br>
    <a href="<?= BASE_URL ?>?action=admin_ai_generate" class="btn-cancel-sm">✕ Annuleren</a>
</div>

<script>
(function () {
    const jobId     = <?= (int)$job['id'] ?>;
    const statusUrl = '<?= BASE_URL ?>?action=admin_ai_job_status&job=' + jobId;
    const previewUrl = '<?= BASE_URL ?>?action=admin_ai_preview&job=' + jobId;
    const msg       = document.getElementById('status-msg');
    let   attempts  = 0;

    async function poll() {
        try {
            const res  = await fetch(statusUrl);
            const data = await res.json();

            if (data.status === 'done') {
                msg.textContent = 'Klaar! Doorsturen naar preview...';
                window.location.href = previewUrl;
                return;
            }
            if (data.status === 'error') {
                msg.innerHTML = '<strong style="color:#e74c3c">Worker fout:</strong> '
                    + (data.error || 'onbekend');
                return; // stop polling
            }
        } catch (_) { /* netwerk fout, gewoon opnieuw proberen */ }

        attempts++;
        msg.textContent = 'Wachten op worker... (' + attempts + 's)';
        setTimeout(poll, 2000);
    }

    setTimeout(poll, 2000);
})();
</script>
