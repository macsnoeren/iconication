<div style="display:flex; flex-direction:column; align-items:center; justify-content:center;
            flex:1; gap:28px; padding:40px 24px; text-align:center;">

    <div style="font-size:4rem;">✅</div>
    <h2 style="font-size:2rem; color:#2c3e50; margin:0;">Bericht begrepen</h2>

    <?php if ($topIntent): ?>
        <div style="background:#eafaf1; border:2px solid #27ae60; border-radius:16px;
                    padding:16px 28px; font-size:1.6rem; color:#1e8449; max-width:480px;">
            💬 <?= htmlspecialchars($topIntent) ?>
        </div>
    <?php endif; ?>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; width:100%; max-width:420px;">
        <a href="<?= BASE_URL ?>"
           class="card card--end" style="padding:24px; border-radius:20px; min-height:120px;">
            <span style="font-size:2rem; margin-top:0;">🔄</span>
            <span style="font-size:1.3rem; margin-top:10px;">Nieuw gesprek</span>
        </a>
        <a href="<?= BASE_URL ?>?topic=<?= $topicId ?>"
           class="card" style="padding:24px; border-radius:20px; min-height:120px;">
            <span style="font-size:2rem; margin-top:0;">↩</span>
            <span style="font-size:1.3rem; margin-top:10px;">Opnieuw</span>
        </a>
    </div>

</div>
