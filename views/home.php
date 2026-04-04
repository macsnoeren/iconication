<h1 style="text-align: center;">Kies een onderwerp</h1>
<div class="topic-list">
    <?php foreach ($topics as $topic): ?>
        <a href="<?= BASE_PATH ?>/topic/<?= $topic['id'] ?>" class="topic-btn">
            <?= htmlspecialchars($topic['name']) ?>
        </a>
    <?php endforeach; ?>
</div>