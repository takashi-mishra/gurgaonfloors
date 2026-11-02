<?php

require_once __DIR__ . '/includes/lead-tracking.php';
require_once __DIR__ . '/includes/lead-storage.php';
require_once __DIR__ . '/get-all-email.php';

ini_set('display_errors', '0');
ini_set('log_errors', '1');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function formResponse(bool $success, string $message, int $statusCode): void
{
    http_response_code($statusCode);
    echo json_encode(
        ['success' => $success, 'message' => $message],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    formResponse(false, 'Invalid request method.', 405);
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (
    !is_string($csrfToken)
    || empty($_SESSION['lead_csrf_token'])
    || !hash_equals($_SESSION['lead_csrf_token'], $csrfToken)
) {
    formResponse(false, 'Your session expired. Refresh the page and try again.', 403);
}

if (!empty($_POST['website'] ?? '')) {
    formResponse(false, 'Unable to submit this enquiry.', 400);
}

$name = leadTrackingClean($_POST['name'] ?? '', 100);
$phone = leadTrackingClean($_POST['phone'] ?? '', 30);
$email = leadTrackingClean($_POST['email'] ?? '', 254);
$project = leadTrackingClean($_POST['project'] ?? '', 180);
$formId = leadTrackingClean($_POST['form_id'] ?? '', 80);
$message = leadTrackingClean($_POST['message'] ?? '', 2000);
$phoneDigits = preg_replace('/\D+/', '', $phone) ?? '';

$allowedFormIds = ['homepage_property_enquiry', 'property_detail_enquiry'];
if (
    $name === ''
    || strlen($name) > 100
    || !preg_match('/^[0-9+\s().-]{7,30}$/', $phone)
    || strlen($phoneDigits) < 10
    || strlen($phoneDigits) > 15
    || filter_var($email, FILTER_VALIDATE_EMAIL) === false
    || $project === ''
    || !in_array($formId, $allowedFormIds, true)
    || (($_POST['consent'] ?? '') !== '1')
) {
    formResponse(false, 'Please enter a valid name, phone number, email, and consent to continue.', 422);
}

$firstTouch = $_SESSION['lead_first_touch'] ?? [];
$lastTouch = $_SESSION['lead_last_touch'] ?? [];
$tracking = [
    'first_touch' => is_array($firstTouch) ? $firstTouch : [],
    'last_touch' => is_array($lastTouch) ? $lastTouch : [],
    'landing_page' => leadTrackingClean($_SESSION['lead_landing_page'] ?? '/', 500),
    'current_page' => leadTrackingClean(
        parse_url((string) ($_SERVER['HTTP_REFERER'] ?? '/'), PHP_URL_PATH) ?: '/',
        500
    ),
];

$leadRecord = [
    'name' => $name,
    'phone' => $phone,
    'email' => $email,
    'project' => $project,
    'message' => $message,
    'form_id' => $formId,
    'consent' => true,
    'first_touch' => $tracking['first_touch'],
    'last_touch' => $tracking['last_touch'],
    'landing_page' => $tracking['landing_page'],
    'submitted_from' => $tracking['current_page'],
];

try {
    $storageResult = leadStorageAppend(
        dirname(__DIR__) . '/data/leads.json',
        $leadRecord
    );
} catch (Throwable $error) {
    error_log('Lead could not be saved: ' . $error->getMessage());
    formResponse(false, 'We could not save your enquiry right now. Please try again or call us.', 500);
}

$deliveryResult = sendLeadEmail($leadRecord, $tracking);

$mailSent = $deliveryResult['mail_sent'];
if (!$mailSent) {
    error_log('Form email could not be accepted by the configured mail transport.');
}

$responseMessage = $mailSent
    ? 'Your enquiry was saved and the email was accepted for sending.'
    : 'Your enquiry was saved in the dashboard, but email sending failed.';

if (!$deliveryResult['database_saved']) {
    $responseMessage .= ' Database save failed; the enquiry remains in the website dashboard.';
}

if ($storageResult['auto_cleaned']) {
    $responseMessage .= ' The lead list was automatically cleared after reaching 50 submissions.';
}

formResponse(true, $responseMessage, 200);
