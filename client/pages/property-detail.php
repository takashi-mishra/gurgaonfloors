<?php

$jsonPath = dirname(__DIR__, 2) . '/data/content.json';

if (!is_file($jsonPath) || !is_readable($jsonPath)) {
    http_response_code(500);
    exit('Content file not found.');
}

$data = json_decode(file_get_contents($jsonPath), true);

if (!is_array($data)) {
    http_response_code(500);
    exit('Invalid content JSON.');
}

if (!function_exists('escapeHtml')) {
    function escapeHtml($value): string {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
    }
}

if (!function_exists('assetUrl')) {
    function assetUrl($path): string {
        $path = str_replace('\\', '/', trim((string) $path));
        $path = ltrim($path, '/');

        if (
            $path === '' ||
            str_contains($path, '..') ||
            preg_match('/[\r\n]/', $path)
        ) {
            return '';
        }

        return implode('/', array_map('rawurlencode', explode('/', $path)));
    }
}

$requestedId = trim($_GET['id'] ?? '');
$selectedProject = null;
$sectionExternalUrl = '';

foreach (($data['projectSections'] ?? []) as $section) {
    foreach (($section['projects'] ?? []) as $project) {
        if (
            is_array($project) &&
            ($project['id'] ?? '') === $requestedId
        ) {
            $selectedProject = $project;
            $sectionExternalUrl = $section['externalUrl'] ?? '';
            break 2;
        }
    }
}

if (!$selectedProject) {
    http_response_code(404);
    exit('Property not found.');
}

$project = $selectedProject;

$image = assetUrl($project['detailImage'] ?? $project['image'] ?? '');

$externalUrl = $project['externalUrl'] ?? $sectionExternalUrl;

if (!filter_var($externalUrl, FILTER_VALIDATE_URL)) {
    $externalUrl = '';
} else {
    $scheme = strtolower(parse_url($externalUrl, PHP_URL_SCHEME) ?? '');

    if (!in_array($scheme, ['http', 'https'], true)) {
        $externalUrl = '';
    }
}

$site = $data['site'] ?? [];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= escapeHtml($project['name'] ?? 'Property') ?>
        | Floors Gurgaon
    </title>

    <meta
        name="description"
        content="<?= escapeHtml($project['shortDescription'] ?? '') ?>"
    >

    <link rel="stylesheet" href="client/css/style.css">
    <link rel="stylesheet" href="client/css/property-detail.css">
</head>

<body>

<header class="detail-header">

    <a href="index.php" class="detail-brand">
        <?= escapeHtml($site['logoText'] ?? 'FLOORS') ?>
        <span><?= escapeHtml($site['logoHighlight'] ?? 'GURGAON') ?></span>
    </a>

    <a href="index.php#projects" class="back-to-projects">
        ← Back to Properties
    </a>

</header>


<main class="property-detail-container">

    <div class="property-detail-heading">

        <div>
            <span class="detail-badge">
                <?= escapeHtml($project['badge'] ?? 'Premium Property') ?>
            </span>

            <h1><?= escapeHtml($project['name'] ?? '') ?></h1>

            <p class="detail-location">
                📍 <?= escapeHtml($project['location'] ?? '') ?>
            </p>
        </div>

        <?php if (!empty($project['rating'])): ?>
            <span class="detail-rating">
                ★ <?= escapeHtml($project['rating']) ?>
            </span>
        <?php endif; ?>

    </div>


    <div class="property-detail-grid">

        <section class="property-main-content">

          <?php if ($image !== ''): ?>

    <div class="property-detail-image">

        <?php if ($externalUrl !== ''): ?>

            <a
                href="<?= escapeHtml($externalUrl) ?>"
                target="_blank"
                rel="noopener noreferrer"
                aria-label="Visit official website"
            >
                <img
                    src="<?= escapeHtml($image) ?>"
                    alt="<?= escapeHtml($project['name'] ?? 'Property') ?>"
                >
            </a>

        <?php else: ?>

            <img
                src="<?= escapeHtml($image) ?>"
                alt="<?= escapeHtml($project['name'] ?? 'Property') ?>"
            >

        <?php endif; ?>

    </div>

<?php endif; ?>


            <div class="property-about">

                <h2>About <?= escapeHtml($project['name'] ?? 'Property') ?></h2>

                <p>
                    <?= escapeHtml(
                        $project['shortDescription'] ??
                        'Contact us to know more about this property.'
                    ) ?>
                </p>

            </div>


            <div class="property-highlights">

                <?php
                $highlights = [
                    'Starting Price' => $project['price'] ?? '',
                    'Configuration' => $project['configuration'] ?? '',
                    'Size' => $project['size'] ?? '',
                    'Status' => $project['status'] ?? '',
                    'Developer' => $project['developer'] ?? ''
                ];
                ?>

                <?php foreach ($highlights as $label => $value): ?>

                    <?php if ($value !== ''): ?>

                        <div class="property-highlight">
                            <span><?= escapeHtml($label) ?></span>
                            <strong><?= escapeHtml($value) ?></strong>
                        </div>

                    <?php endif; ?>

                <?php endforeach; ?>

            </div>


            <?php if ($externalUrl !== ''): ?>
                <a
                    href="<?= escapeHtml($externalUrl) ?>"
                    class="official-website-link"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    Visit Official Website ↗
                </a>
            <?php endif; ?>

        </section>


        <aside class="property-contact-card">

            <h2>Interested in <?= escapeHtml($project['name'] ?? 'this property') ?>?</h2>

            <p>Get floor plans, price list and free site visit.</p>

            <a
                class="property-contact-button"
                href="tel:<?= escapeHtml($site['phoneLink'] ?? '') ?>"
            >
                Contact Now
            </a>

            <a
                class="property-phone"
                href="tel:<?= escapeHtml($site['phoneLink'] ?? '') ?>"
            >
                ☎ <?= escapeHtml($site['phone'] ?? '') ?>
            </a>

        </aside>

    </div>

</main>

</body>
</html>