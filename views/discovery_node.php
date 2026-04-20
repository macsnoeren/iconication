<?php
$node = $nodeMap[$currentId] ?? null;
if (!$node): ?>
    <p>Fout: node niet gevonden.</p>
<?php return; endif;

$options = $node['options'] ?? [];
?>
<div style="display:flex; flex-direction:column; flex:1; min-height:0; gap:10px;">

    <?php if (!empty($sentence)): ?>
    <div style="background:#f0e8fc; border-left:4px solid #9b59b6; border-radius:0 10px 10px 0;
                padding:8px 14px; font-size:1rem; color:#5b2c6f; flex-shrink:0;">
        💬 <?= htmlspecialchars($sentence) ?>
    </div>
    <?php endif; ?>

    <div class="grid grid--4" style="flex:1; min-height:0;">
        <?php foreach ($options as $idx => $opt):
            $isTopic = isset($opt['target_topic']);
            $href    = BASE_URL . "?action=discovery_nav&dnode={$node['id']}&oidx=$idx";
        ?>
            <a href="<?= $href ?>" class="card card--sm <?= $isTopic ? 'card--end' : '' ?>">
                <?php if (!empty($opt['image_url'])): ?>
                    <img src="<?= htmlspecialchars($opt['image_url']) ?>" alt="">
                <?php endif; ?>
                <span><?= htmlspecialchars($opt['label']) ?></span>
            </a>
        <?php endforeach; ?>
    </div>

</div>
