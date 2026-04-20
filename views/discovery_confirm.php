<div style="display:flex; flex-direction:column; align-items:center; justify-content:center;
            gap:28px; padding:40px 24px; text-align:center; flex:1;">

    <div style="font-size:0.95rem; color:#888; text-transform:uppercase; letter-spacing:1px;">
        Wil je dit zeggen?
    </div>

    <div style="background:#f0e8fc; border:3px solid #9b59b6; border-radius:24px;
                padding:28px 36px; font-size:2rem; font-weight:bold; color:#4a235a;
                max-width:520px; line-height:1.4; box-shadow: 0 4px 20px rgba(155,89,182,0.15);">
        💬 <?= htmlspecialchars($suggestedMessage) ?>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; width:100%; max-width:420px;">
        <a href="<?= BASE_URL ?>?action=discovery_confirm_yes"
           class="card card--end"
           style="padding:20px; border-radius:20px; min-height:100px;">
            <span style="font-size:2rem; margin-top:0;">✓</span>
            <span style="font-size:1.4rem; margin-top:8px;">Ja, dit klopt</span>
        </a>
        <a href="<?= BASE_URL ?>"
           class="card"
           style="padding:20px; border-radius:20px; border-color:#e74c3c; min-height:100px;">
            <span style="font-size:2rem; margin-top:0;">✗</span>
            <span style="font-size:1.4rem; margin-top:8px;">Nee, opnieuw</span>
        </a>
    </div>

</div>
