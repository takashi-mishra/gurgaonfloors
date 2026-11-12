<?php

require_once dirname(__DIR__) . '/includes/lead-tracking.php';
$leadTracking = leadTrackingInitialize();
$leadCsrfToken = leadTrackingCsrfToken();
$leadFormId = 'property_detail_enquiry';

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
    function escapeHtml($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('assetUrl')) {
    function assetUrl($path): string
    {
        $path = str_replace('\\', '/', trim((string) $path));
        $path = ltrim($path, '/');

        if ($path === '' || str_contains($path, '..') || preg_match('/[\r\n]/', $path)) {
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
        if (is_array($project) && ($project['id'] ?? '') === $requestedId) {
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
$detail = is_array($project['detailPage'] ?? null) ? $project['detailPage'] : [];
$heroImage = assetUrl($detail['heroImage'] ?? $project['detailImage'] ?? $project['image'] ?? '');
$aboutImage = assetUrl($detail['aboutImage'] ?? $project['detailImage'] ?? $project['image'] ?? '');
$externalUrl = $project['externalUrl'] ?? $sectionExternalUrl;
$site = is_array($data['site'] ?? null) ? $data['site'] : [];

if (!filter_var($externalUrl, FILTER_VALIDATE_URL)) {
    $externalUrl = '';
} else {
    $scheme = strtolower(parse_url($externalUrl, PHP_URL_SCHEME) ?? '');
    if (!in_array($scheme, ['http', 'https'], true)) {
        $externalUrl = '';
    }
}

$sectionDefaults = [
    'hero' => true,
    'resources' => true,
    'about' => true,
    'highlights' => true,
    'gallery' => true,
    'reachout' => true,
];
$visibility = array_replace($sectionDefaults, is_array($detail['visibility'] ?? null) ? $detail['visibility'] : []);
$sectionNames = ['resources', 'about', 'highlights', 'gallery', 'reachout'];
$sectionOrder = array_values(array_filter(
    is_array($detail['sectionOrder'] ?? null) ? $detail['sectionOrder'] : $sectionNames,
    static fn ($sectionName) => in_array($sectionName, $sectionNames, true)
));
$sectionOrder = array_values(array_unique(array_merge($sectionOrder, $sectionNames)));
$defaults = [
    'heroEyebrow' => $project['badge'] ?? 'A considered way of living',
    'heroTitle' => $project['name'] ?? 'Featured property',
    'heroDescription' => $project['shortDescription'] ?? 'Discover the details, plans and latest availability for this project.',
    'heroButton' => 'Enquire Now',
    'resourcesTitle' => 'Everything you need to decide',
    'resourcesDescription' => 'Request the latest project information directly from our property team.',
    'aboutTitle' => 'About the Project',
    'aboutDescription' => $project['shortDescription'] ?? 'Speak with our property team to learn more about this project.',
    'aboutButton' => 'Download Brochure',
    'highlightsTitle' => 'Project Highlights',
    'highlightsDescription' => 'Key details and helpful information about this project.',
    'galleryTitle' => 'A closer look',
    'galleryDescription' => !empty($detail['gallery']) ? 'Explore images shared for this project.' : 'Explore a selection of premium residences from our collection.',
    'reachoutTitle' => 'Let’s find your place',
    'reachoutDescription' => 'Share your details and our property team will help you with the next steps.',
    'reachoutButton' => 'Send Enquiry',
];
$detail = array_replace($defaults, $detail);
$resourceDefaults = [
    ['title' => 'Project Brochure', 'description' => 'Get a closer look at the project, its features and key details.', 'buttonText' => 'Download Brochure'],
    ['title' => 'Floor Plans', 'description' => 'Explore the available layouts and find the right fit for you.', 'buttonText' => 'View Floor Plans'],
    ['title' => 'Price & Payment Plan', 'description' => 'Request the latest price list and payment plan information.', 'buttonText' => 'View Price List'],
    ['title' => 'Schedule a Visit', 'description' => 'Speak with our team to arrange a call or plan a site visit.', 'buttonText' => 'Schedule a Call'],
];
$resourceCards = is_array($detail['resourceCards'] ?? null) ? $detail['resourceCards'] : [];
$resourceCards = array_slice(array_replace($resourceDefaults, $resourceCards), 0, 4);

$projectFact = static function (string $key, string $fallback) use ($project): string {
    $value = trim((string) ($project[$key] ?? ''));
    return $value !== '' ? $value : $fallback;
};
$defaultHighlights = [
    ['icon' => '⌖', 'title' => 'Location', 'description' => $projectFact('location', 'Ask our team for location details.')],
    ['icon' => '₹', 'title' => 'Starting price', 'description' => $projectFact('price', 'Contact us for current pricing.')],
    ['icon' => '⌂', 'title' => 'Configuration', 'description' => $projectFact('configuration', 'Ask about available layouts.')],
    ['icon' => '↔', 'title' => 'Available sizes', 'description' => $projectFact('size', 'Ask about available sizes.')],
    ['icon' => '◷', 'title' => 'Project status', 'description' => $projectFact('status', 'Request the latest project updates.')],
    ['icon' => '✦', 'title' => 'Developer', 'description' => $projectFact('developer', 'Speak with our property team.')],
    ['icon' => '▤', 'title' => 'Project brochure', 'description' => 'Request detailed project information.'],
    ['icon' => '▦', 'title' => 'Floor plans', 'description' => 'Ask for the latest available floor plans.'],
    ['icon' => '◉', 'title' => 'Site visit', 'description' => 'Arrange a guided visit with our team.'],
];
$highlights = is_array($detail['highlights'] ?? null) && count($detail['highlights']) > 0
    ? $detail['highlights']
    : $defaultHighlights;
$gallery = is_array($detail['gallery'] ?? null) ? array_slice($detail['gallery'], 0, 6) : [];
$gallery = array_values(array_filter($gallery, static fn ($image) => assetUrl($image) !== ''));
$hasProjectGallery = count($gallery) > 0;
if (!$gallery) {
    $projectImage = $project['detailImage'] ?? $project['image'] ?? '';
    if (assetUrl($projectImage) !== '') {
        $gallery[] = $projectImage;
    }
    foreach (($data['projectSections'] ?? []) as $propertySection) {
        foreach (($propertySection['projects'] ?? []) as $galleryProject) {
            if (!is_array($galleryProject)) {
                continue;
            }
            foreach ([$galleryProject['detailImage'] ?? '', $galleryProject['image'] ?? ''] as $imagePath) {
                if (assetUrl($imagePath) !== '' && !in_array($imagePath, $gallery, true)) {
                    $gallery[] = $imagePath;
                }
                if (count($gallery) >= 6) {
                    break 3;
                }
            }
        }
    }
}
$phone = (string) ($site['phone'] ?? '');
$phoneLink = (string) ($site['phoneLink'] ?? '');
$location = (string) ($project['location'] ?? '');
$stats = [
    'Starting price' => $project['price'] ?? '',
    'Configuration' => $project['configuration'] ?? '',
    'Project status' => $project['status'] ?? '',
    'Developer' => $project['developer'] ?? '',
];

$sectionMarkup = [];

if (!empty($visibility['resources'])) {
    ob_start();
    ?>
    <section class="detail-section detail-resources" id="project-information">
        <div class="detail-shell">
            <div class="detail-section-heading">
                <span class="detail-kicker">PROJECT INFORMATION</span>
                <h2><?= escapeHtml($detail['resourcesTitle']) ?></h2>
                <p><?= escapeHtml($detail['resourcesDescription']) ?></p>
            </div>
            <div class="resource-grid">
                <?php foreach ($resourceCards as $index => $card): ?>
                    <?php if (!is_array($card) || array_key_exists('enabled', $card) && !$card['enabled']) continue; ?>
                    <article class="resource-card">
                        <span class="resource-number">0<?= (int) $index + 1 ?></span>
                        <div>
                            <h3><?= escapeHtml($card['title'] ?? '') ?></h3>
                            <p><?= escapeHtml($card['description'] ?? '') ?></p>
                        </div>
                        <button
                            type="button"
                            class="detail-button detail-button-outline"
                            data-enquiry
                            data-project="<?= escapeHtml(($project['name'] ?? 'Property') . ' — ' . ($card['title'] ?? 'Project information')) ?>"
                        ><?= escapeHtml($card['buttonText'] ?? 'Enquire Now') ?><span aria-hidden="true">↗</span></button>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php
    $sectionMarkup['resources'] = ob_get_clean();
}

if (!empty($visibility['about'])) {
    ob_start();
    ?>
    <section class="detail-section detail-about" id="about-project">
        <div class="detail-shell about-layout">
            <div class="about-image-wrap">
                <?php if ($aboutImage !== ''): ?>
                    <img src="<?= escapeHtml($aboutImage) ?>" alt="<?= escapeHtml(($project['name'] ?? 'Project') . ' exterior') ?>" loading="lazy">
                <?php endif; ?>
                <?php if (!empty($project['badge'])): ?><span class="about-image-tag"><?= escapeHtml($project['badge']) ?></span><?php endif; ?>
            </div>
            <div class="about-copy">
                <span class="detail-kicker">THE RESIDENCE</span>
                <h2><?= escapeHtml($detail['aboutTitle']) ?></h2>
                <p><?= nl2br(escapeHtml($detail['aboutDescription'])) ?></p>
                <?php if ($stats): ?>
                    <dl class="project-facts">
                        <?php foreach ($stats as $label => $value): if (trim((string) $value) === '') continue; ?>
                            <div><dt><?= escapeHtml($label) ?></dt><dd><?= escapeHtml($value) ?></dd></div>
                        <?php endforeach; ?>
                    </dl>
                <?php endif; ?>
                <button type="button" class="detail-button detail-button-dark" data-enquiry data-project="<?= escapeHtml(($project['name'] ?? 'Property') . ' — Brochure') ?>">
                    <?= escapeHtml($detail['aboutButton']) ?><span aria-hidden="true">↗</span>
                </button>
            </div>
        </div>
    </section>
    <?php
    $sectionMarkup['about'] = ob_get_clean();
}

if (!empty($visibility['highlights'])) {
    ob_start();
    ?>
    <section class="detail-section detail-highlights" id="project-highlights">
        <div class="detail-shell">
            <div class="detail-section-heading">
                <span class="detail-kicker">AT A GLANCE</span>
                <h2><?= escapeHtml($detail['highlightsTitle']) ?></h2>
                <p><?= escapeHtml($detail['highlightsDescription']) ?></p>
            </div>
            <div class="highlight-grid">
                <?php foreach ($highlights as $highlight): if (!is_array($highlight)) continue; ?>
                    <article class="highlight-card">
                        <span class="highlight-icon" aria-hidden="true"><?= escapeHtml($highlight['icon'] ?? '✦') ?></span>
                        <div><h3><?= escapeHtml($highlight['title'] ?? '') ?></h3><p><?= escapeHtml($highlight['description'] ?? '') ?></p></div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php
    $sectionMarkup['highlights'] = ob_get_clean();
}

if (!empty($visibility['gallery']) && $gallery) {
    ob_start();
    ?>
    <section class="detail-section detail-gallery" id="project-gallery">
        <div class="detail-shell">
            <div class="detail-section-heading">
                <span class="detail-kicker">PROJECT GALLERY</span>
                <h2><?= escapeHtml($detail['galleryTitle']) ?></h2>
                <p><?= escapeHtml($detail['galleryDescription']) ?></p>
            </div>
        </div>
        <div class="gallery-marquee" aria-label="Project image gallery">
            <div class="gallery-track">
                <?php foreach ([false, true] as $isDuplicate): ?>
                    <div class="gallery-group" <?= $isDuplicate ? 'aria-hidden="true"' : '' ?>>
                        <?php foreach ($gallery as $index => $galleryImage): ?>
                            <figure class="gallery-item">
                                <img src="<?= escapeHtml(assetUrl($galleryImage)) ?>" alt="<?= escapeHtml(($hasProjectGallery ? ($project['name'] ?? 'Project') . ' gallery image ' : 'Gurgaon property inspiration image ') . ($index + 1)) ?>" loading="lazy">
                            </figure>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php
    $sectionMarkup['gallery'] = ob_get_clean();
}

if (!empty($visibility['reachout'])) {
    ob_start();
    ?>
    <section class="detail-section detail-reachout" id="reach-out">
        <div class="detail-shell reachout-layout">
            <div class="reachout-copy">
                <span class="detail-kicker">PERSONAL GUIDANCE</span>
                <h2><?= escapeHtml($detail['reachoutTitle']) ?></h2>
                <p><?= escapeHtml($detail['reachoutDescription']) ?></p>
                <?php if ($phone !== '' && $phoneLink !== ''): ?>
                    <a class="reachout-phone" href="tel:<?= escapeHtml($phoneLink) ?>">☎ <span><?= escapeHtml($phone) ?></span></a>
                <?php endif; ?>
            </div>
            <form class="reachout-form" id="projectDetailReachoutForm" method="post" action="client/form.php">
                <p class="reachout-form-title">Request a callback</p>
                <input type="hidden" name="csrf_token" value="<?= escapeHtml($leadCsrfToken) ?>">
                <input type="hidden" name="form_id" value="<?= escapeHtml($leadFormId) ?>">
                <input type="hidden" name="project" value="<?= escapeHtml($project['name'] ?? 'Property Enquiry') ?>">
                <div class="enquiry-honeypot" aria-hidden="true" hidden>
                    <label>Leave this field empty<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                </div>
                <label>Name<input type="text" name="name" placeholder="Your name" maxlength="100" autocomplete="name" required></label>
                <label>Phone<input type="tel" name="phone" placeholder="Your phone number" maxlength="30" autocomplete="tel" required></label>
                <label>Email<input type="email" name="email" placeholder="Your email address" maxlength="254" autocomplete="email" required></label>
                <label class="reachout-consent"><input type="checkbox" name="consent" value="1" required><span>I agree to Floors Gurgaon <a href="#privacy">privacy policy</a>.</span></label>
                <button type="submit" class="detail-button detail-button-gold"><?= escapeHtml($detail['reachoutButton']) ?><span aria-hidden="true">→</span></button>
                <p class="enquiry-message reachout-message" aria-live="polite"></p>
            </form>
        </div>
    </section>
    <?php
    $sectionMarkup['reachout'] = ob_get_clean();
}

$heroVisible = !empty($visibility['hero']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escapeHtml($project['name'] ?? 'Property') ?> | Floors Gurgaon</title>
    <meta name="description" content="<?= escapeHtml($project['shortDescription'] ?? '') ?>">
    <link rel="stylesheet" href="client/css/style.css">
    <link rel="stylesheet" href="client/css/property-detail.css?v=<?= filemtime(__DIR__ . '/../css/property-detail.css') ?>">
    <link rel="stylesheet" href="client/css/project-sections.css?v=<?= filemtime(__DIR__ . '/../css/project-sections.css') ?>">
    <link rel="stylesheet" href="client/css/footer.css?v=<?= filemtime(__DIR__ . '/../css/footer.css') ?>">
</head>
<body class="project-detail-page<?= $heroVisible ? ' has-detail-hero' : '' ?>">
<header class="detail-header">
    <a href="index.php" class="detail-brand">
        <?= escapeHtml($site['logoText'] ?? 'FLOORS') ?>
        <span><?= escapeHtml($site['logoHighlight'] ?? 'GURGAON') ?></span>
    </a>
    <nav class="detail-header-actions" aria-label="Property navigation">
        <a href="index.php#projects" class="back-to-projects">← <span>All Properties</span></a>
        <?php if ($phone !== '' && $phoneLink !== ''): ?><a class="detail-header-phone" href="tel:<?= escapeHtml($phoneLink) ?>">☎ <span><?= escapeHtml($phone) ?></span></a><?php endif; ?>
        <button type="button" class="detail-header-cta" data-enquiry data-project="<?= escapeHtml($project['name'] ?? 'Property Enquiry') ?>">Enquire</button>
    </nav>
</header>

<main>
    <?php if ($heroVisible): ?>
        <section class="detail-hero">
            <?php if ($heroImage !== ''): ?><img class="detail-hero-image" src="<?= escapeHtml($heroImage) ?>" alt="<?= escapeHtml(($project['name'] ?? 'Project') . ' exterior') ?>" fetchpriority="high"><?php endif; ?>
            <div class="detail-hero-shade"></div>
            <div class="detail-shell detail-hero-content">
                <div class="hero-copy">
                    <?php if ($detail['heroEyebrow'] !== ''): ?><span class="hero-eyebrow"><i></i><?= escapeHtml($detail['heroEyebrow']) ?></span><?php endif; ?>
                    <h1><?= escapeHtml($detail['heroTitle']) ?></h1>
                    <?php if ($location !== ''): ?><p class="hero-location">⌖ <?= escapeHtml($location) ?></p><?php endif; ?>
                    <p class="hero-description"><?= escapeHtml($detail['heroDescription']) ?></p>
                    <div class="hero-actions">
                        <button type="button" class="detail-button detail-button-gold" data-enquiry data-project="<?= escapeHtml($project['name'] ?? 'Property Enquiry') ?>"><?= escapeHtml($detail['heroButton']) ?><span aria-hidden="true">→</span></button>
                        <?php if ($externalUrl !== ''): ?><a href="<?= escapeHtml($externalUrl) ?>" class="hero-website-link" target="_blank" rel="noopener noreferrer">Official website ↗</a><?php endif; ?>
                    </div>
                </div>
                <div class="hero-side-note"><span>01</span><i></i><span><?= escapeHtml($location ?: 'Gurgaon, India') ?></span></div>
                <?php if ($project['price'] ?? ''): ?><div class="hero-price"><span>Starting from</span><strong><?= escapeHtml($project['price']) ?></strong></div><?php endif; ?>
            </div>
            <a class="hero-scroll" href="#project-information"><span></span>Discover the project</a>
        </section>
    <?php endif; ?>

    <?php foreach ($sectionOrder as $sectionName): ?>
        <?php if (isset($sectionMarkup[$sectionName])): ?><?= $sectionMarkup[$sectionName] ?><?php endif; ?>
    <?php endforeach; ?>
</main>

<?php require dirname(__DIR__) . '/Component/footer.php'; ?>
<?php require dirname(__DIR__) . '/Component/enquiry-modal.php'; ?>
<script src="client/js/project-sections.js?v=<?= filemtime(__DIR__ . '/../js/project-sections.js') ?>" defer></script>
</body>
</html>
