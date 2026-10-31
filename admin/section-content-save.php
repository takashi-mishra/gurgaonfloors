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

function cm_post_string($value, int $max = 1000): string {
    if (!is_scalar($value)) return '';
    $value = trim((string)$value);
    if (strlen($value) > $max) throw new RuntimeException('A field exceeds the allowed length.');
    return $value;
}
function cm_safe_url(string $url): bool {
    if ($url === '' || str_starts_with($url, '#') || str_starts_with($url, '/') || str_starts_with($url, 'tel:') || str_starts_with($url, 'mailto:')) return true;
    $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
    return in_array($scheme, ['http','https'], true) && filter_var($url, FILTER_VALIDATE_URL) !== false;
}
function cm_array_field(string $key): array {
    $value = $_POST[$key] ?? [];
    if (!is_array($value)) throw new RuntimeException('Invalid card data received.');
    if (count($value) > 100) throw new RuntimeException('Maximum 100 cards per group are allowed.');
    return $value;
}
function cm_save_rows(array $rows, string $kind): array {
    $out = [];
    foreach ($rows as $row) {
        if (!is_array($row)) continue;
        $enabled = isset($row['enabled']) && (string)$row['enabled'] === '1';
        if ($kind === 'builder') {
            $name = cm_post_string($row['name'] ?? '', 120);
            if ($name === '') throw new RuntimeException('Every builder card needs a name.');
            $url = cm_post_string($row['url'] ?? '#', 500);
            if (!cm_safe_url($url)) throw new RuntimeException('Invalid builder card URL.');
            $out[] = ['name'=>$name,'count'=>cm_post_string($row['count'] ?? '',100),'icon'=>cm_post_string($row['icon'] ?? '',40),'url'=>$url,'enabled'=>$enabled];
        } elseif ($kind === 'locations') {
            $name = cm_post_string($row['name'] ?? '', 120);
            if ($name === '') throw new RuntimeException('Every location card needs a name.');
            $url = cm_post_string($row['url'] ?? '#', 500);
            if (!cm_safe_url($url)) throw new RuntimeException('Invalid location card URL.');
            $out[] = ['name'=>$name,'description'=>cm_post_string($row['description'] ?? '',500),'icon'=>cm_post_string($row['icon'] ?? '',40),'url'=>$url,'featured'=>isset($row['featured']) && (string)$row['featured']==='1','enabled'=>$enabled];
        } elseif ($kind === 'stats') {
            $value = cm_post_string($row['value'] ?? '',80);
            $label = cm_post_string($row['label'] ?? '',120);
            if ($value === '' || $label === '') throw new RuntimeException('Every statistic needs both a value and a label.');
            $out[] = ['value'=>$value,'label'=>$label,'enabled'=>$enabled];
        } elseif ($kind === 'benefits') {
            $title = cm_post_string($row['title'] ?? '',140);
            if ($title === '') throw new RuntimeException('Every benefit card needs a title.');
            $out[] = ['icon'=>cm_post_string($row['icon'] ?? '',40),'title'=>$title,'description'=>cm_post_string($row['description'] ?? '',700),'enabled'=>$enabled];
        }
    }
    return array_values($out);
}
function cm_write_json(string $path, array $data): void {
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
    $builder = $data['builderSection'] ?? [];
    if (!is_array($builder)) $builder = [];
    $builder['title'] = cm_post_string($_POST['builder_title'] ?? 'Explore Floors by Builder',180);
    $builder['highlight'] = cm_post_string($_POST['builder_highlight'] ?? 'Builder',100);
    $builder['description'] = cm_post_string($_POST['builder_description'] ?? '',700);
    $builder['buttonText'] = cm_post_string($_POST['builder_button_text'] ?? 'All Builders',80);
    $builder['buttonUrl'] = cm_post_string($_POST['builder_button_url'] ?? '#',500);
    if (!cm_safe_url($builder['buttonUrl'])) throw new RuntimeException('Invalid Builder button URL.');
    $builder['items'] = cm_save_rows(cm_array_field('builder'),'builder');
    $data['builderSection'] = $builder;

    $locations = $data['locationSection'] ?? [];
    if (!is_array($locations)) $locations = [];
    $locations['title'] = cm_post_string($_POST['locations_title'] ?? '',180);
    $locations['description'] = cm_post_string($_POST['locations_description'] ?? '',700);
    $locations['buttonText'] = cm_post_string($_POST['locations_button_text'] ?? '',100);
    $locations['buttonUrl'] = cm_post_string($_POST['locations_button_url'] ?? '#',500);
    if (!cm_safe_url($locations['buttonUrl'])) throw new RuntimeException('Invalid Locations button URL.');
    $locations['items'] = cm_save_rows(cm_array_field('locations'),'locations');
    $data['locationSection'] = $locations;

    $investment = $data['investmentSection'] ?? [];
    if (!is_array($investment)) $investment = [];
    $investment['title'] = cm_post_string($_POST['investment_title'] ?? ($investment['title'] ?? 'Investment Benefits'),180);
    $investment['description'] = cm_post_string($_POST['investment_description'] ?? ($investment['description'] ?? ''),700);
    $investment['buttonText'] = cm_post_string($_POST['investment_button_text'] ?? ($investment['buttonText'] ?? ''),100);
    $investment['buttonUrl'] = cm_post_string($_POST['investment_button_url'] ?? ($investment['buttonUrl'] ?? '#contact'),500);
    if (!cm_safe_url($investment['buttonUrl'])) throw new RuntimeException('Invalid Investment button URL.');
    $investment['stats'] = cm_save_rows(cm_array_field('stats'),'stats');
    $investment['benefits'] = cm_save_rows(cm_array_field('benefits'),'benefits');
    $data['investmentSection'] = $investment;

    cm_write_json($jsonPath, $data);
    header('Location: section-content.php?saved=' . rawurlencode('Section content saved successfully.'));
    exit;
} catch (RuntimeException $e) {
    header('Location: section-content.php?error=' . rawurlencode($e->getMessage()));
    exit;
}
