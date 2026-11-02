<?php
$projectSections = $projectSectionsToRender ?? ($data['projectSections'] ?? []);

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
