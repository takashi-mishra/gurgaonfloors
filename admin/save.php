<?php
session_start();
if (empty($_SESSION['admin_logged_in'])) { http_response_code(401); exit('Please log in to continue.'); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed.'); }
$postedToken = (string)($_POST['csrf_token'] ?? '');
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $postedToken)) { http_response_code(403); exit('Security token expired. Refresh the admin page and try again.'); }
$root = dirname(__DIR__);
$jsonPath = $root . '/data/content.json';
if (!is_file($jsonPath) || !is_readable($jsonPath) || !is_writable($jsonPath)) { http_response_code(500); exit('content.json must exist and be readable/writable by PHP.'); }
$raw = file_get_contents($jsonPath);
$data = json_decode((string)$raw, true);
if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) { http_response_code(500); exit('content.json contains invalid JSON. Restore a valid backup first.'); }
$action = (string)($_POST['action'] ?? '');
function field(string $name, int $max = 500): string { $v = trim((string)($_POST[$name] ?? '')); if (strlen($v) > $max) $v = substr($v, 0, $max); return $v; }
function safeUrl(string $url): bool { return $url === '' || str_starts_with($url, '#') || str_starts_with($url, '/') || str_starts_with($url, 'tel:') || str_starts_with($url, 'mailto:') || filter_var($url, FILTER_VALIDATE_URL); }
function saveJson(string $path, array $data): void {
    $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($encoded === false) throw new RuntimeException('Could not encode JSON data.');
    $tmp = $path . '.tmp'; $fp = fopen($tmp, 'wb');
    if (!$fp) throw new RuntimeException('Could not create temporary file. Check folder permissions.');
    if (!flock($fp, LOCK_EX)) { fclose($fp); @unlink($tmp); throw new RuntimeException('Could not lock temporary file.'); }
    $written = fwrite($fp, $encoded . PHP_EOL); fflush($fp); flock($fp, LOCK_UN); fclose($fp);
    if ($written === false || $written < strlen($encoded)) { @unlink($tmp); throw new RuntimeException('Failed to write all content.'); }
    if (!rename($tmp, $path)) { @unlink($tmp); throw new RuntimeException('Could not replace content.json. Check folder permissions.'); }
}
function uploadHeroImage(string $root): string {
    if (!isset($_FILES['image_file']) || !is_array($_FILES['image_file'])) throw new RuntimeException('Please choose an image file.');
    $file = $_FILES['image_file'];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $err = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($err === UPLOAD_ERR_NO_FILE) throw new RuntimeException('Please choose an image file.');
        if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) throw new RuntimeException('Image is too large. Maximum allowed size is 8 MB.');
        throw new RuntimeException('Image upload failed. Please try again.');
    }
    if (($file['size'] ?? 0) > 8 * 1024 * 1024) throw new RuntimeException('Image is too large. Maximum allowed size is 8 MB.');
    if (!is_uploaded_file($file['tmp_name'] ?? '')) throw new RuntimeException('Invalid upload received.');
    $info = @getimagesize($file['tmp_name']);
    if (!$info || empty($info['mime'])) throw new RuntimeException('The uploaded file is not a valid image.');
    $extensions = ['image/jpeg'=>'jpg', 'image/png'=>'png', 'image/webp'=>'webp', 'image/gif'=>'gif'];
    $mime = (string)$info['mime'];
    if (!isset($extensions[$mime])) throw new RuntimeException('Unsupported image type. Use JPG, PNG, WEBP or GIF.');
    $imageDir = $root . '/images';
    if (!is_dir($imageDir) && !mkdir($imageDir, 0755, true) && !is_dir($imageDir)) throw new RuntimeException('Could not create the images folder.');
    if (!is_writable($imageDir)) throw new RuntimeException('The images folder is not writable by PHP.');
    $filename = 'hero-' . bin2hex(random_bytes(10)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], $imageDir . '/' . $filename)) throw new RuntimeException('Could not save uploaded image. Check images folder permissions.');
    return 'images/' . $filename;
}
function deleteManagedImageIfUnused(string $root, string $oldPath, array $slides): void {
    $oldPath = ltrim(str_replace('\\', '/', $oldPath), '/');
    if ($oldPath === '' || !str_starts_with($oldPath, 'images/') || basename($oldPath) !== substr($oldPath, 7)) return;
    foreach ($slides as $slide) if (($slide['image'] ?? '') === $oldPath) return;
    $full = $root . '/' . $oldPath;
    if (is_file($full) && !is_link($full)) @unlink($full);
}
try {
    if ($action === 'seo') {
        $title = field('title', 180); $description = field('description', 500); $keywords = field('keywords', 1000); $language = field('language', 10);
        if ($title === '' || $description === '') throw new RuntimeException('SEO title and description are required.');
        if (!in_array($language, ['en','hi'], true)) $language = 'en';
        $data['seo'] = array_merge($data['seo'] ?? [], ['title'=>$title,'description'=>$description,'keywords'=>$keywords,'language'=>$language]);
        $message = 'SEO settings saved successfully.';
    } elseif ($action === 'hero') {
        $heading = field('heading', 180); $description = field('description', 500);
        if ($heading === '' || $description === '') throw new RuntimeException('Hero heading and description are required.');
        $data['hero'] = array_merge($data['hero'] ?? [], ['eyebrow'=>field('eyebrow',100),'heading'=>$heading,'description'=>$description,'primaryButton'=>field('primaryButton',80),'secondaryButton'=>field('secondaryButton',80)]);
        $message = 'Hero content saved successfully.';
    } elseif ($action === 'navbar') {
        $data['site'] = array_merge($data['site'] ?? [], ['logoText'=>field('logoText',60),'logoHighlight'=>field('logoHighlight',60),'phone'=>field('phone',50),'phoneLink'=>field('phoneLink',50)]);
        $labels = $_POST['nav_label'] ?? []; $urls = $_POST['nav_url'] ?? []; $nav = [];
        if (!is_array($labels) || !is_array($urls)) throw new RuntimeException('Invalid navigation data.');
        if (count($labels) > 30 || count($labels) !== count($urls)) throw new RuntimeException('Please check the navigation links. Maximum 30 links are allowed.');
        foreach ($labels as $i => $label) {
            $label = trim((string)$label); $url = trim((string)($urls[$i] ?? ''));
            if ($label === '' && $url === '') continue;
            if ($label === '' || $url === '') throw new RuntimeException('Every navigation link needs both a label and a URL.');
            if (strlen($label) > 80 || strlen($url) > 500 || !safeUrl($url)) throw new RuntimeException('A navigation URL is invalid. Use a local path, #section, tel:, mailto:, or a complete https URL.');
            $nav[] = ['label'=>$label,'url'=>$url];
        }
        $data['navigation'] = $nav;
        $message = 'Navbar settings and links saved successfully.';
    } elseif (in_array($action, ['image_add','image_update','image_delete'], true)) {
        if (!isset($data['hero']) || !is_array($data['hero'])) $data['hero'] = [];
        $slides = $data['hero']['slides'] ?? [];
        if (!is_array($slides)) $slides = [];
        if ($action === 'image_add') {
            if (count($slides) >= 20) throw new RuntimeException('Maximum 20 hero slides allowed.');
            $label = field('label', 100); if ($label === '') throw new RuntimeException('Slide label is required.');
            $newPath = uploadHeroImage($root);
            $slides[] = ['image'=>$newPath, 'alt'=>field('alt',180) ?: $label, 'label'=>$label, 'location'=>field('location',120)];
            $message = 'New hero image uploaded and added successfully.';
        } else {
            $index = filter_var($_POST['index'] ?? null, FILTER_VALIDATE_INT);
            if ($index === false || $index === null || !isset($slides[$index]) || !is_array($slides[$index])) throw new RuntimeException('That hero slide no longer exists. Refresh the page and try again.');
            $oldPath = (string)($slides[$index]['image'] ?? '');
            if ($action === 'image_delete') {
                array_splice($slides, $index, 1);
                $data['hero']['slides'] = array_values($slides);
                saveJson($jsonPath, $data);
                deleteManagedImageIfUnused($root, $oldPath, $data['hero']['slides']);
                header('Location: index.php?saved=' . rawurlencode('Hero slide deleted successfully.')); exit;
            }
            $move = (string)($_POST['move'] ?? '');
            if ($move === 'up' || $move === 'down') {
                $target = $move === 'up' ? $index - 1 : $index + 1;
                if (isset($slides[$target])) { [$slides[$index], $slides[$target]] = [$slides[$target], $slides[$index]]; }
            }
            $label = field('label', 100); if ($label === '') throw new RuntimeException('Slide label is required.');
            $slides[$index]['label'] = $label;
            $slides[$index]['location'] = field('location',120);
            $slides[$index]['alt'] = field('alt',180) ?: $label;
            if (isset($_FILES['image_file']) && (($_FILES['image_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE)) {
                $newPath = uploadHeroImage($root);
                $slides[$index]['image'] = $newPath;
            }
            $message = ($move === 'up' || $move === 'down') ? 'Hero slide order updated.' : 'Hero slide details saved successfully.';
        }
        $data['hero']['slides'] = array_values($slides);
        if (isset($newPath) && isset($oldPath) && $oldPath !== '' && $oldPath !== $newPath) deleteManagedImageIfUnused($root, $oldPath, $data['hero']['slides']);
    } else { throw new RuntimeException('Unknown save action.'); }
    if (!isset($data['hero']['slides']) && isset($slides) && is_array($slides)) $data['hero']['slides'] = array_values($slides);
    saveJson($jsonPath, $data);
    header('Location: index.php?saved=' . rawurlencode($message)); exit;
} catch (RuntimeException $e) {
    header('Location: index.php?error=' . rawurlencode($e->getMessage()) . (str_starts_with($action, 'image_') ? '#section-images' : '')); exit;
}
