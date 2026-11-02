<?php
$enquiryEscape = static fn ($value): string => htmlspecialchars(
    (string) $value,
    ENT_QUOTES | ENT_SUBSTITUTE,
    'UTF-8'
);
?>
<div class="enquiry-modal" id="projectEnquiryModal" aria-hidden="true" hidden>
    <div class="enquiry-modal-overlay" data-close-enquiry></div>
    <div
        class="enquiry-modal-content"
        role="dialog"
        aria-modal="true"
        aria-labelledby="enquiryTitle"
    >
        <button type="button" class="enquiry-close" data-close-enquiry aria-label="Close enquiry form">&times;</button>
        <div class="enquiry-modal-header">
            <h2 id="enquiryTitle">👍 Yes, I'm Interested</h2>
            <p>Fill out the form below and we'll get back to you</p>
        </div>

        <form id="projectEnquiryForm" method="post" action="client/form.php">
            <p class="enquiry-selected-property">
                Property: <strong id="selectedProjectName">Selected Property</strong>
            </p>

            <input type="hidden" name="csrf_token" value="<?= $enquiryEscape($leadCsrfToken) ?>">
            <input type="hidden" name="form_id" value="<?= $enquiryEscape($leadFormId) ?>">
            <input type="hidden" name="project" value="General Property Enquiry">

            <div class="enquiry-honeypot" aria-hidden="true" hidden>
                <label>Leave this field empty<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
            </div>

            <input type="text" name="name" placeholder="👤 Name" maxlength="100" autocomplete="name" required>
            <input type="tel" name="phone" placeholder="📞 Number" maxlength="30" autocomplete="tel" required>
            <input type="email" name="email" placeholder="✉️ Email" maxlength="254" autocomplete="email" required>

            <label class="enquiry-privacy">
                <input type="checkbox" name="consent" value="1" required>
                <span>I agree to Floors Gurgaon <a href="#privacy">privacy policy</a>.</span>
            </label>

            <button type="submit" class="enquiry-submit">Contact Now</button>
            <p class="enquiry-message" id="enquiryMessage" aria-live="polite"></p>
        </form>

    </div>
</div>
<div class="lead-toast" id="leadToast" role="status" aria-live="polite" hidden>
    Thank you! Your enquiry has been sent. We’ll get back to you shortly.
</div>
