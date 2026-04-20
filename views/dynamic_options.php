<?php $count = count($options); $cols = ($count <= 2) ? 1 : 2; ?>

<div style="display:flex; flex-direction:column; flex:1; min-height:0; gap:10px;">

    <?php if (!empty($sentence)): ?>
    <div style="background:#f0e8fc; border-left:4px solid #9b59b6; border-radius:0 10px 10px 0;
                padding:8px 14px; font-size:1rem; color:#5b2c6f; flex-shrink:0;">
        💬 <?= htmlspecialchars($sentence) ?>
    </div>
    <?php endif; ?>

    <div style="display:grid; grid-template-columns:<?= str_repeat('1fr ', $cols) ?>;
                grid-template-rows: repeat(<?= ceil($count / $cols) ?>, 1fr);
                gap:14px; flex:1; min-height:0;">
        <?php foreach ($options as $idx => $opt):
            $isFinal = $isComplete || !empty($opt['suggested_message']);
            $href    = BASE_URL . "?action=dynamic_select&oidx=$idx";
        ?>
            <a href="<?= $href ?>" class="card card--sm <?= $isFinal ? 'card--end' : '' ?>">
                <?php if (!empty($opt['image_url'])): ?>
                    <img src="<?= htmlspecialchars($opt['image_url']) ?>" alt="">
                <?php endif; ?>
                <span><?= htmlspecialchars($opt['label']) ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if ($canGoBack): ?>
    <div style="flex-shrink:0; padding:6px 0;">
        <a href="<?= BASE_URL ?>?action=dynamic_back"
           style="color:#aaa; font-size:0.9rem; text-decoration:none; display:inline-block; padding:4px 8px;">
            ← Vorige opties
        </a>
    </div>
    <?php endif; ?>

</div>
