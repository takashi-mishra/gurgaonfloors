<?php
$projectSections = $data['projectSections'] ?? [];

if (!is_array($projectSections)) {
    $projectSections = [];
}
?>

<?php foreach ($projectSections as $section): ?>

    <?php
    if (!is_array($section)) {
        continue;
    }

    $projects = $section['projects'] ?? [];

    if (!is_array($projects)) {
        $projects = [];
    }
    ?>

    <section
        class="project-section"
        id="<?= escapeHtml($section['id'] ?? 'projects') ?>"
    >

        <div class="project-container">

            <div class="project-section-header">

                <div>
                    <h2 class="project-section-title">
                        <?php if (!empty($section['titleHighlight'])): ?>
                            <span>
                                <?= escapeHtml($section['titleHighlight']) ?>
                            </span>
                        <?php endif; ?>

                        <?= escapeHtml($section['title'] ?? '') ?>
                    </h2>

                    <p class="project-section-description">
                        <?= escapeHtml($section['description'] ?? '') ?>
                    </p>
                </div>

                <?php if (!empty($section['viewAllUrl'])): ?>
                    <a
                        href="<?= escapeHtml($section['viewAllUrl']) ?>"
                        class="project-view-all"
                    >
                        <?= escapeHtml($section['viewAllText'] ?? 'View All') ?>
                        <span aria-hidden="true">→</span>
                    </a>
                <?php endif; ?>

            </div>


            <div class="project-grid">

                <?php foreach ($projects as $project): ?>

                    <?php
                    if (!is_array($project)) {
                        continue;
                    }

                    $image = assetUrl($project['image'] ?? '');
                    $projectName = $project['name'] ?? 'Property';
                    ?>

                    <article class="project-card">

                        <div class="project-card-image">

                            <?php if ($image !== ''): ?>
                                <img
                                    src="<?= escapeHtml($image) ?>"
                                    alt="<?= escapeHtml($project['name'] ?? 'Property') ?>"
                                    loading="lazy"
                                >
                                <a
                                 href="property.php?id=<?= urlencode($project['id'] ?? '') ?>"
                                 class="project-card-link"
                                 aria-label="View <?= escapeHtml($projectName) ?> details"
                                ></a>
                            <?php endif; ?>

                            <?php if (!empty($project['badge'])): ?>
                                <span class="project-badge">
                                    <?= escapeHtml($project['badge']) ?>
                                </span>
                            <?php endif; ?>

                        </div>


                        <div class="project-card-content">

                            <div class="project-card-heading">

                                <h3>
                                    <?= escapeHtml($projectName) ?>
                                </h3>

                                <?php if (!empty($project['rating'])): ?>
                                    <span class="project-rating">
                                        ★ <?= escapeHtml($project['rating']) ?>
                                    </span>
                                <?php endif; ?>

                            </div>


                            <p class="project-location">
                                <?= escapeHtml($project['location'] ?? '') ?>
                            </p>


                            <div class="project-card-footer">

                                <strong class="project-price">
                                    <?= escapeHtml($project['price'] ?? '') ?>
                                </strong>

                                <?php if (!empty($project['enquiryEnabled'])): ?>

                                    <button
                                        type="button"
                                        class="project-enquiry-btn"
                                        data-enquiry
                                        data-project="<?= escapeHtml($projectName) ?>"
                                    >
                                        Enquire Now
                                    </button>

                                <?php endif; ?>

                            </div>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        </div>

    </section>

<?php endforeach; ?>


<!-- COMMON ENQUIRY POPUP -->

<div
    class="enquiry-modal"
    id="projectEnquiryModal"
    aria-hidden="true"
>

    <div class="enquiry-modal-overlay" data-close-enquiry></div>

    <div
        class="enquiry-modal-content"
        role="dialog"
        aria-modal="true"
        aria-labelledby="enquiryTitle"
    >

        <button
            type="button"
            class="enquiry-close"
            data-close-enquiry
            aria-label="Close enquiry form"
        >
            &times;
        </button>

        <div class="enquiry-modal-header">

            <h2 id="enquiryTitle">
                👍 Yes, I'm Interested
            </h2>

            <p>
                Fill out the form below and we'll get back to you
            </p>

        </div>


        <form id="projectEnquiryForm">

            <p class="enquiry-selected-property">
                Property:
                <strong id="selectedProjectName">Selected Property</strong>
            </p>

            <input
                type="text"
                name="name"
                placeholder="👤 Name"
                required
            >

            <input
                type="tel"
                name="phone"
                placeholder="📞 Number"
                pattern="[0-9+\-\s]{10,15}"
                required
            >

            <input
                type="email"
                name="email"
                placeholder="✉️ Email"
                required
            >

            <label class="enquiry-privacy">
                <input type="checkbox" required>
                <span>
                    I agree to Floors Gurgaon
                    <a href="#">privacy policy</a>.
                </span>
            </label>

            <button type="submit" class="enquiry-submit">
                Contact Now
            </button>

            <p
                class="enquiry-message"
                id="enquiryMessage"
                aria-live="polite"
            ></p>

        </form>

    </div>

</div>