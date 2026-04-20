<div style="display:flex; flex-direction:column; align-items:center; justify-content:center;
            flex:1; gap:28px; padding:40px 24px; text-align:center;">

    <div style="font-size:4rem;">💬</div>
    <h2 style="font-size:1.6rem; color:#2c3e50; margin:0;">Dit bedoel je?</h2>

    <?php if (!empty($suggestedMessage)): ?>
        <div style="background:#eafaf1; border:2px solid #27ae60; border-radius:16px;
                    padding:16px 28px; font-size:1.6rem; color:#1e8449; max-width:520px; line-height:1.4;">
            <?= htmlspecialchars($suggestedMessage) ?>
        </div>
    <?php elseif (!empty($sentence)): ?>
        <div style="background:#f0f4ff; border:2px solid #3498db; border-radius:16px;
                    padding:16px 28px; font-size:1.3rem; color:#2c3e50; max-width:520px;">
            <?= htmlspecialchars($sentence) ?>
        </div>
    <?php endif; ?>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; width:100%; max-width:420px;">

        <form method="POST" action="<?= BASE_URL ?>?action=session_confirm">
            <input type="hidden" name="sentence" value="<?= htmlspecialchars($suggestedMessage ?? $sentence) ?>">
            <button type="submit" class="card card--end"
                    style="width:100%; padding:24px; border-radius:20px; min-height:120px; border:none; cursor:pointer;">
                <span style="font-size:2.5rem;">✅</span>
                <span style="font-size:1.3rem; margin-top:10px;">Ja, dit!</span>
            </button>
        </form>

        <form method="POST" action="<?= BASE_URL ?>?action=session_back">
            <button type="submit" class="card"
                    style="width:100%; padding:24px; border-radius:20px; min-height:120px; border:none; cursor:pointer;">
                <span style="font-size:2.5rem;">↩</span>
                <span style="font-size:1.3rem; margin-top:10px;">Nee, terug</span>
            </button>
        </form>

    </div>
</div>
