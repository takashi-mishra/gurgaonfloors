<?php
session_start();
if (empty($_SESSION['admin_logged_in'])) { http_response_code(401); exit('Please log in to continue.'); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed.'); }

$token = (string)($_POST['csrf_token'] ?? '');
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    http_response_code(403);
    exit('Security token expired. Refresh the page and try again.');
}

$root = dirname(__DIR__);
$jsonPath = $root . '/data/content.json';
if (!is_file($jsonPath) || !is_readable($jsonPath) || !is_writable($jsonPath)) {
    http_response_code(500);
    exit('content.json must exist and be readable/writable by PHP.');
}
$data = json_decode((string)file_get_contents($jsonPath), true);
if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    exit('content.json contains invalid JSON. Restore a valid backup first.');
}

function faq_post_string($value, int $max): string {
    if (!is_scalar($value)) throw new RuntimeException('Invalid FAQ field received.');
    $value = trim((string)$value);
    if (strlen($value) > $max) throw new RuntimeException('A FAQ field exceeds the allowed length.');
    return $value;
}
function faq_write_json(string $path, array $data): void {
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) throw new RuntimeException('Could not encode JSON.');
    $tmp = $path . '.tmp';
    $fp = fopen($tmp, 'wb');
    if (!$fp) throw new RuntimeException('Could not create temporary JSON file. Check permissions.');
    if (!flock($fp, LOCK_EX)) { fclose($fp); @unlink($tmp); throw new RuntimeException('Could not lock JSON file.'); }
    $written = fwrite($fp, $json . PHP_EOL);
    fflush($fp); flock($fp, LOCK_UN); fclose($fp);
    if ($written === false || $written < strlen($json)) { @unlink($tmp); throw new RuntimeException('Could not write complete JSON data.'); }
    if (!rename($tmp, $path)) { @unlink($tmp); throw new RuntimeException('Could not replace content.json. Check permissions.'); }
}

try {
    $section = $data['faqSection'] ?? [];
    if (!is_array($section)) $section = [];

    $id = faq_post_string($_POST['faq_id'] ?? 'faq', 80);
    if ($id === '' || !preg_match('/^[A-Za-z][A-Za-z0-9_-]*$/', $id)) {
        throw new RuntimeException('Section ID may contain letters, numbers, hyphens and underscores, and must start with a letter.');
    }
    $section['id'] = $id;
    $section['eyebrow'] = faq_post_string($_POST['faq_eyebrow'] ?? '', 160);
    $section['title'] = faq_post_string($_POST['faq_title'] ?? 'Frequently Asked Questions', 180);
    $section['description'] = faq_post_string($_POST['faq_description'] ?? '', 1000);
    $section['bottomText'] = faq_post_string($_POST['faq_bottom_text'] ?? '', 500);

    $postedItems = $_POST['items'] ?? [];
    if (!is_array($postedItems) || count($postedItems) > 100) {
        throw new RuntimeException('Invalid FAQ list. Maximum 100 FAQs are allowed.');
    }
    $items = [];
    foreach ($postedItems as $item) {
        if (!is_array($item)) continue;
        $question = faq_post_string($item['question'] ?? '', 500);
        $answer = faq_post_string($item['answer'] ?? '', 5000);
        if ($question === '' && $answer === '') continue;
        if ($question === '' || $answer === '') {
            throw new RuntimeException('Every FAQ needs both a question and an answer.');
        }
        $items[] = [
            'question' => $question,
            'answer' => $answer,
            'enabled' => isset($item['enabled']) && (string)$item['enabled'] === '1'
        ];
    }
    $section['items'] = array_values($items);
    $data['faqSection'] = $section;

    faq_write_json($jsonPath, $data);
    header('Location: faq.php?saved=' . rawurlencode('FAQ content saved successfully.'));
    exit;
} catch (RuntimeException $e) {
    header('Location: faq.php?error=' . rawurlencode($e->getMessage()));
    exit;
}
