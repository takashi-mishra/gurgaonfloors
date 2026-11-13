<?php
$customCardSection = is_array($customCardSection ?? null) ? $customCardSection : [];
$customCardItems = $customCardSection['items'] ?? [];
if (!is_array($customCardItems)) {
    $customCardItems = [];
}
$customSectionId = (string) ($customCardSection['id'] ?? '');
?>
<section class="info-section custom-card-section" id="card-section-<?= htmlspecialchars($customSectionId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
    <div class="info-container">
        <div class="info-heading">
            <h2><?= htmlspecialchars((string) ($customCardSection['title'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h2>
            <?php if (!empty($customCardSection['description'])): ?>
                <p><?= htmlspecialchars((string) $customCardSection['description'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
            <?php endif; ?>
        </div>

        <div class="custom-card-grid">
            <?php foreach ($customCardItems as $card): ?>
                <?php
                if (!is_array($card) || (array_key_exists('enabled', $card) && empty($card['enabled']))) {
                    continue;
                }
                $cardUrl = trim((string) ($card['url'] ?? ''));
                $cardClass = 'custom-section-card' . (!empty($card['featured']) ? ' featured' : '');
                ?>
                <?php if ($cardUrl !== ''): ?>
                    <a class="<?= $cardClass ?>" href="<?= htmlspecialchars($cardUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                <?php else: ?>
                    <article class="<?= $cardClass ?>">
                <?php endif; ?>
                    <?php if (!empty($card['icon'])): ?>
                        <span class="custom-section-card-icon"><?= htmlspecialchars((string) $card['icon'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                    <?php endif; ?>
                    <strong><?= htmlspecialchars((string) ($card['title'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong>
                    <?php if (!empty($card['description'])): ?>
                        <small><?= htmlspecialchars((string) $card['description'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></small>
                    <?php endif; ?>
                <?php if ($cardUrl !== ''): ?>
                    </a>
                <?php else: ?>
                    </article>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($customCardSection['buttonText'])): ?>
            <a class="info-bottom-button custom-card-section-button" href="<?= htmlspecialchars((string) ($customCardSection['buttonUrl'] ?? '#contact'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                <?= htmlspecialchars((string) $customCardSection['buttonText'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
            </a>
        <?php endif; ?>
    </div>
</section>
