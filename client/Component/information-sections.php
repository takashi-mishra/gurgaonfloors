<?php
$builderSection = $data['builderSection'] ?? [];
$locationSection = $data['locationSection'] ?? [];
$investmentSection = $data['investmentSection'] ?? [];

if (!function_exists('infoSectionEscape')) {
    function infoSectionEscape($value): string
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
    }
}
?>

<!-- ================= BUILDER SECTION ================= -->

<?php if (
    (!isset($informationSectionKey) || $informationSectionKey === 'builders')
    && !empty($builderSection)
): ?>
<section class="info-section builder-section" id="builders">
    <div class="info-container">

        <div class="info-heading-row">
            <div>
                <h2>
                    <?= infoSectionEscape($builderSection['title'] ?? '') ?>
                    <span><?= infoSectionEscape($builderSection['highlight'] ?? '') ?></span>
                </h2>
                <p><?= infoSectionEscape($builderSection['description'] ?? '') ?></p>
            </div>

            <a class="info-view-all"
               href="<?= infoSectionEscape($builderSection['buttonUrl'] ?? '#') ?>">
                <?= infoSectionEscape($builderSection['buttonText'] ?? 'All Builders') ?>
                <span>→</span>
            </a>
        </div>

        <div class="builder-grid">
            <?php foreach (($builderSection['items'] ?? []) as $item): ?>
                <a class="builder-card"
                   href="<?= infoSectionEscape($item['url'] ?? '#') ?>">

                    <span class="builder-icon">
                        <?= infoSectionEscape($item['icon'] ?? '▦') ?>
                    </span>

                    <strong><?= infoSectionEscape($item['name'] ?? '') ?></strong>
                    <small><?= infoSectionEscape($item['count'] ?? '') ?></small>
                </a>
            <?php endforeach; ?>
        </div>

    </div>
</section>
<?php endif; ?>


<!-- ================= LOCATION SECTION ================= -->

<?php if (
    (!isset($informationSectionKey) || $informationSectionKey === 'locations')
    && !empty($locationSection)
): ?>
<section class="info-section location-section" id="locations">
    <div class="info-container">

        <div class="info-heading">
            <h2>
                <span class="heading-icon">📍</span>
                <?= infoSectionEscape($locationSection['title'] ?? '') ?>
            </h2>
            <p><?= infoSectionEscape($locationSection['description'] ?? '') ?></p>
        </div>

        <div class="location-grid">
            <?php foreach (($locationSection['items'] ?? []) as $item): ?>
                <a
                    class="location-card <?= !empty($item['featured']) ? 'featured' : '' ?>"
                    href="<?= infoSectionEscape($item['url'] ?? '#') ?>"
                >
                    <span class="location-icon">
                        <?= infoSectionEscape($item['icon'] ?? '⌖') ?>
                    </span>

                    <strong><?= infoSectionEscape($item['name'] ?? '') ?></strong>

                    <small><?= infoSectionEscape($item['description'] ?? '') ?></small>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($locationSection['buttonText'])): ?>
            <a class="info-bottom-button"
               href="<?= infoSectionEscape($locationSection['buttonUrl'] ?? '#') ?>">
                ⌖ <?= infoSectionEscape($locationSection['buttonText']) ?>
            </a>
        <?php endif; ?>

    </div>
</section>
<?php endif; ?>


<!-- ================= INVESTMENT SECTION ================= -->

<?php if (
    (!isset($informationSectionKey) || $informationSectionKey === 'investment')
    && !empty($investmentSection)
): ?>
<section class="info-section investment-section" id="investment">
    <div class="info-container">

        <div class="info-heading">
            <h2>
                <span class="heading-icon">▤</span>
                <?= infoSectionEscape($investmentSection['title'] ?? '') ?>
            </h2>
            <p><?= infoSectionEscape($investmentSection['description'] ?? '') ?></p>
        </div>

        <div class="investment-stats">
            <?php foreach (($investmentSection['stats'] ?? []) as $stat): ?>
                <article class="investment-stat">
                    <strong><?= infoSectionEscape($stat['value'] ?? '') ?></strong>
                    <span><?= infoSectionEscape($stat['label'] ?? '') ?></span>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="investment-benefits">
            <?php foreach (($investmentSection['benefits'] ?? []) as $benefit): ?>
                <article class="investment-benefit">
                    <h3>
                        <span><?= infoSectionEscape($benefit['icon'] ?? '') ?></span>
                        <?= infoSectionEscape($benefit['title'] ?? '') ?>
                    </h3>

                    <p><?= infoSectionEscape($benefit['description'] ?? '') ?></p>
                </article>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($investmentSection['buttonText'])): ?>
            <a class="info-bottom-button"
               href="<?= infoSectionEscape($investmentSection['buttonUrl'] ?? '#contact') ?>">
                ▦ <?= infoSectionEscape($investmentSection['buttonText']) ?>
            </a>
        <?php endif; ?>

    </div>
</section>
<?php endif; ?>