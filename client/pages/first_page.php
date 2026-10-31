<?php

// ========================================
// 1. LOAD JSON DATA
// ========================================

$jsonPath = dirname(__DIR__, 2) . '/data/content.json';

if (!is_file($jsonPath) || !is_readable($jsonPath)) {
    http_response_code(500);
    exit('Website content file not found. Please check data/content.json.');
}

$jsonContent = file_get_contents($jsonPath);

if ($jsonContent === false) {
    http_response_code(500);
    exit('Unable to read website content.');
}

$data = json_decode($jsonContent, true);

if (!is_array($data)) {
    http_response_code(500);
    exit('Invalid JSON in data/content.json.');
}


// ========================================
// 2. HELPER FUNCTIONS
// ========================================

function escapeHtml($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

/**
 * Convert a local asset path into a safe URL relative to the project root.
 * Example: images/hero-1.jpg -> images/hero-1.jpg
 */
function assetUrl($path): string
{
    $path = str_replace('\\', '/', trim((string) $path));
    $path = ltrim($path, '/');

    // Only allow local asset paths.
    if (
        $path === '' ||
        str_contains($path, '..') ||
        preg_match('/[\r\n]/', $path)
    ) {
        return '';
    }

    return implode(
        '/',
        array_map('rawurlencode', explode('/', $path))
    );
}


// ========================================
// 3. EXTRACT CONTENT
// ========================================

$seo = $data['seo'] ?? [];
$site = $data['site'] ?? [];
$navigation = $data['navigation'] ?? [];
$hero = $data['hero'] ?? [];

$slides = $hero['slides'] ?? [];

if (!is_array($slides)) {
    $slides = [];
}

$homepageSectionKeys = ['projects', 'builders', 'locations', 'investment'];
$homepageLayout = $data['homepageLayout'] ?? [];
$homepageSectionOrder = $homepageLayout['order'] ?? $homepageSectionKeys;

if (!is_array($homepageSectionOrder)) {
    $homepageSectionOrder = $homepageSectionKeys;
}

$homepageSectionOrder = array_values(array_unique(array_filter(
    $homepageSectionOrder,
    function ($key) use ($homepageSectionKeys) {
        return is_string($key) && in_array($key, $homepageSectionKeys, true);
    }
)));

foreach ($homepageSectionKeys as $sectionKey) {
    if (!in_array($sectionKey, $homepageSectionOrder, true)) {
        $homepageSectionOrder[] = $sectionKey;
    }
}

$homepageSectionEnabled = $homepageLayout['enabled'] ?? [];

if (!is_array($homepageSectionEnabled)) {
    $homepageSectionEnabled = [];
}

// Keep only valid slide objects with an image path.
$slides = array_values(array_filter($slides, function ($slide) {
    return is_array($slide)
        && !empty($slide['image'])
        && assetUrl($slide['image']) !== '';
}));

$pageTitle = $seo['title'] ?? 'Floors Gurgaon';
$pageDescription = $seo['description'] ?? '';
$pageKeywords = $seo['keywords'] ?? '';
$language = $seo['language'] ?? 'en';

$firstSlide = $slides[0] ?? [];


// ========================================
// 4. RENDER PAGE
// ========================================

?>

<!DOCTYPE html>
<html lang="<?= escapeHtml($language) ?>">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title><?= escapeHtml($pageTitle) ?></title>

    <meta
        name="description"
        content="<?= escapeHtml($pageDescription) ?>"
    >

    <meta
        name="keywords"
        content="<?= escapeHtml($pageKeywords) ?>"
    >

    <meta name="robots" content="index, follow">
    <meta name="theme-color" content="#111a2e">

    <!-- Main stylesheet -->
    <link rel="stylesheet" href="client/css/style.css">
    <link rel="stylesheet" href="client/css/project-sections.css">
    <link rel="stylesheet" href="client/css/information-sections.css">
    <link rel="stylesheet" href="client/css/faq.css">
    <link rel="stylesheet" href="client/css/footer.css">
</head>

<body>

    <!-- ==================================
         NAVBAR
    =================================== -->

    <header class="site-header">

        <nav class="navbar container" aria-label="Main navigation">

            <a
                href="#home"
                class="brand"
                aria-label="Floors Gurgaon Home"
            >
                <span class="brand-name">
                    <?= escapeHtml($site['logoText'] ?? 'FLOORS') ?>
                </span>

                <span class="brand-location">
                    <?= escapeHtml($site['logoHighlight'] ?? 'GURGAON') ?>
                </span>
            </a>

            <button
                class="menu-toggle"
                id="menuToggle"
                type="button"
                aria-label="Open navigation menu"
                aria-expanded="false"
                aria-controls="navMenu"
            >
                <span></span>
                <span></span>
                <span></span>
            </button>

            <div class="nav-menu" id="navMenu">

                <ul class="nav-links">

                    <?php foreach ($navigation as $item): ?>

                        <?php if (!is_array($item)) continue; ?>

                        <li>
                            <a
                                href="<?= escapeHtml($item['url'] ?? '#') ?>"
                                class="nav-link"
                            >
                                <?= escapeHtml($item['label'] ?? '') ?>
                            </a>
                        </li>

                    <?php endforeach; ?>

                </ul>

                <div class="nav-actions">

                    <a
                        class="nav-phone"
                        href="tel:<?= escapeHtml($site['phoneLink'] ?? '') ?>"
                    >
                        <span class="phone-icon" aria-hidden="true">
                            ☎
                        </span>

                        <span>
                            <small>Talk to our expert</small>

                            <strong>
                                <?= escapeHtml($site['phone'] ?? '') ?>
                            </strong>
                        </span>
                    </a>

                    <a class="nav-cta" href="#contact">
                        Enquire Now
                        <span aria-hidden="true">↗</span>
                    </a>

                </div>

            </div>

        </nav>

    </header>


    <!-- ==================================
         MAIN CONTENT
    =================================== -->

    <main>

        <!-- HERO SLIDER -->

        <section class="hero hero-slider" id="home">

            <!-- Background images -->

            <div class="hero-backgrounds" aria-hidden="true">

                <?php foreach ($slides as $index => $slide): ?>

                    <?php $imageUrl = assetUrl($slide['image']); ?>

                    <div
                        class="hero-background <?= $index === 0 ? 'active' : '' ?>"
                        style="background-image: url('<?= escapeHtml($imageUrl) ?>')"
                        data-label="<?= escapeHtml($slide['label'] ?? '') ?>"
                        data-location="<?= escapeHtml($slide['location'] ?? '') ?>"
                    ></div>

                <?php endforeach; ?>

                <?php if (empty($slides)): ?>

                    <div class="hero-background hero-fallback active"></div>

                <?php endif; ?>

            </div>

            <!-- Dark overlay -->

            <div class="hero-overlay" aria-hidden="true"></div>

            <div class="container hero-inner">

                <!-- Hero text -->

                <div class="hero-content">

                    <div class="hero-badge">
                        <span class="badge-dot"></span>

                        <?= escapeHtml(
                            $hero['eyebrow'] ?? 'PREMIUM LIVING IN GURGAON'
                        ) ?>
                    </div>

                    <h1>
                        <?= escapeHtml(
                            $hero['heading'] ?? 'Find Your Perfect Home.'
                        ) ?>
                    </h1>

                    <p class="hero-description">
                        <?= escapeHtml(
                            $hero['description'] ?? 'Explore premium homes in Gurgaon.'
                        ) ?>
                    </p>

                    <div class="hero-actions">

                        <a href="#projects" class="button button-primary">
                            <?= escapeHtml(
                                $hero['primaryButton'] ?? 'Explore Properties'
                            ) ?>

                            <span aria-hidden="true">→</span>
                        </a>

                        <a href="#contact" class="button button-secondary">
                            <?= escapeHtml(
                                $hero['secondaryButton'] ?? 'Talk to an Expert'
                            ) ?>
                        </a>

                    </div>

                    <div class="hero-trust">

                        <div class="trust-item">
                            <span class="trust-check">✓</span>
                            <span>Premium Properties</span>
                        </div>

                        <div class="trust-item">
                            <span class="trust-check">✓</span>
                            <span>Prime Locations</span>
                        </div>

                        <div class="trust-item">
                            <span class="trust-check">✓</span>
                            <span>Expert Guidance</span>
                        </div>

                    </div>

                </div>


                <!-- Current slide information -->

                <div class="hero-slide-info">

                    <span class="slide-location" id="slideLocation">
                        <?= escapeHtml(
                            $firstSlide['location'] ?? 'Gurgaon, Haryana'
                        ) ?>
                    </span>

                    <h2 id="slideLabel">
                        <?= escapeHtml(
                            $firstSlide['label'] ?? 'Luxury Living'
                        ) ?>
                    </h2>

                    <div class="slider-controls">

                        <span class="slide-counter">
                            <span id="currentSlide">01</span>

                            <span class="counter-divider"></span>

                            <span id="totalSlides">
                                <?= str_pad(
                                    (string) max(1, count($slides)),
                                    2,
                                    '0',
                                    STR_PAD_LEFT
                                ) ?>
                            </span>
                        </span>

                        <div
                            class="slider-dots"
                            id="sliderDots"
                            aria-label="Choose a hero image"
                        >

                            <?php foreach ($slides as $index => $slide): ?>

                                <button
                                    type="button"
                                    class="slider-dot <?= $index === 0 ? 'active' : '' ?>"
                                    data-slide="<?= $index ?>"
                                    aria-label="Show slide <?= $index + 1 ?>"
                                    aria-pressed="<?= $index === 0 ? 'true' : 'false' ?>"
                                ></button>

                            <?php endforeach; ?>

                        </div>

                    </div>

                </div>

            </div>

        </section>


        <?php foreach ($homepageSectionOrder as $sectionKey): ?>
            <?php
            if (
                array_key_exists($sectionKey, $homepageSectionEnabled)
                && empty($homepageSectionEnabled[$sectionKey])
            ) {
                continue;
            }

            if ($sectionKey === 'projects') {
                require __DIR__ . '/../Component/project-sections.php';
                continue;
            }

            $informationSectionKey = $sectionKey;
            require __DIR__ . '/../Component/information-sections.php';
            ?>
        <?php endforeach; ?>

       <?php
             require dirname(__DIR__) . '/Component/faq-section.php';
        ?>

        <?php require __DIR__ . '/../Component/footer.php'; ?>

    </main>


    <!-- Main JavaScript -->
    <script src="client/js/script.js" defer></script>
    <script src="client/js/project-sections.js" defer></script>
    <script src="client/js/faq.js" defer></script>

</body>
</html>