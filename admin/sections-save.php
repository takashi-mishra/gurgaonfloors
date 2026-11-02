<?php
session_start();

if (empty($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    exit('Please log in.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

$token = (string) ($_POST['csrf_token'] ?? '');
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    http_response_code(403);
    exit('Security token expired. Refresh and try again.');
}

$root = dirname(__DIR__);
$path = $root . '/data/content.json';
if (!is_file($path) || !is_readable($path) || !is_writable($path)) {
    http_response_code(500);
    exit('data/content.json must be readable and writable by PHP.');
}

$data = json_decode((string) file_get_contents($path), true);
if (!is_array($data)) {
    http_response_code(500);
    exit('Invalid content.json. Restore a valid backup.');
}

$layoutHelper = $root . '/client/includes/homepage-layout.php';
if (!is_file($layoutHelper) || !is_readable($layoutHelper)) {
    http_response_code(500);
    exit('Homepage layout configuration is unavailable.');
}
require_once $layoutHelper;

$definitions = homepageLayoutDefinitions($data);
$postedOrder = explode(',', (string) ($_POST['order'] ?? ''));
$order = homepageLayoutOrder(['order' => $postedOrder], $definitions);
$postedEnabled = $_POST['enabled'] ?? [];
if (!is_array($postedEnabled)) {
    $postedEnabled = [];
}

$enabled = [];
foreach ($definitions as $key => $definition) {
    $enabled[$key] = in_array($key, $postedEnabled, true);
}

$data['homepageLayout'] = [
    'order' => $order,
    'enabled' => $enabled,
];

$json = json_encode(
    $data,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
if ($json === false) {
    http_response_code(500);
    exit('Could not encode JSON.');
}

$temporaryPath = $path . '.tmp';
$file = fopen($temporaryPath, 'wb');
if ($file === false) {
    http_response_code(500);
    exit('Could not create temporary file.');
}

if (!flock($file, LOCK_EX)) {
    fclose($file);
    @unlink($temporaryPath);
    http_response_code(500);
    exit('Could not lock JSON file.');
}

$contents = $json . PHP_EOL;
$written = fwrite($file, $contents);
fflush($file);
flock($file, LOCK_UN);
fclose($file);

if ($written === false || $written < strlen($contents) || !rename($temporaryPath, $path)) {
    @unlink($temporaryPath);
    http_response_code(500);
    exit('Could not save content.json. Check permissions.');
}

header('Location: sections.php?saved=' . rawurlencode('Homepage section order and visibility saved successfully.'));
exit;
