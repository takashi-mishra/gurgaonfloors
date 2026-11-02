<?php
session_start();

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once dirname(__DIR__) . '/client/includes/lead-storage.php';

$storagePath = dirname(__DIR__) . '/data/leads.json';
$saved = (string) ($_GET['saved'] ?? '');
$error = (string) ($_GET['error'] ?? '');

function leadsEscape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function leadCsvSafe($value): string
{
    $value = (string) $value;
    if (preg_match('/^\s*[=+\-@]/', $value)) {
        return "'" . $value;
    }

    return $value;
}

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    try {
        $leadData = leadStorageRead($storagePath);
    } catch (Throwable $exception) {
        error_log('Lead export failed: ' . $exception->getMessage());
        http_response_code(500);
        exit('Could not read leads for export.');
    }

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="floors-gurgaon-leads.csv"');
    header('Cache-Control: no-store');
    $output = fopen('php://output', 'wb');
    if ($output === false) {
        http_response_code(500);
        exit('Could not create CSV export.');
    }

    fwrite($output, "\xEF\xBB\xBF");
    fputcsv($output, [
        'Lead ID',
        'Submitted At',
        'Name',
        'Phone',
        'Email',
        'Property / Enquiry',
        'Message',
        'Form ID',
        'First Source',
        'First Medium',
        'First Campaign',
        'First Keyword',
        'First Referrer',
        'Latest Source',
        'Latest Medium',
        'Latest Campaign',
        'Latest Keyword',
        'Latest Referrer',
        'Landing Page',
        'Submitted From',
    ], ',', '"', '');

    foreach ($leadData['leads'] as $lead) {
        if (!is_array($lead)) {
            continue;
        }

        $first = is_array($lead['first_touch'] ?? null) ? $lead['first_touch'] : [];
        $last = is_array($lead['last_touch'] ?? null) ? $lead['last_touch'] : [];
        $values = [
            $lead['id'] ?? '',
            $lead['submitted_at'] ?? '',
            $lead['name'] ?? '',
            $lead['phone'] ?? '',
            $lead['email'] ?? '',
            $lead['project'] ?? '',
            $lead['message'] ?? '',
            $lead['form_id'] ?? '',
            $first['source'] ?? '',
            $first['medium'] ?? '',
            $first['campaign'] ?? '',
            $first['keyword'] ?? '',
            $first['referrer'] ?? '',
            $last['source'] ?? '',
            $last['medium'] ?? '',
            $last['campaign'] ?? '',
            $last['keyword'] ?? '',
            $last['referrer'] ?? '',
            $lead['landing_page'] ?? '',
            $lead['submitted_from'] ?? '',
        ];

        fputcsv($output, array_map('leadCsvSafe', $values), ',', '"', '');
    }

    fclose($output);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (
        !is_string($token)
        || empty($_SESSION['csrf_token'])
        || !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        http_response_code(403);
        exit('Security token expired. Refresh and try again.');
    }

    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'delete') {
            $leadId = $_POST['lead_id'] ?? '';
            if (!is_string($leadId) || !preg_match('/^[a-f0-9]{24}$/', $leadId)) {
                throw new RuntimeException('Invalid lead selected.');
            }

            if (!leadStorageDelete($storagePath, $leadId)) {
                throw new RuntimeException('That lead was not found.');
            }

            $saved = 'Lead deleted.';
        } elseif ($action === 'clear') {
            $deletedCount = leadStorageClear($storagePath);
            $saved = $deletedCount . ' stored lead(s) deleted.';
        } else {
            throw new RuntimeException('Invalid dashboard action.');
        }
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
        error_log('Lead dashboard action failed: ' . $exception->getMessage());
    }

    $query = $error !== ''
        ? 'error=' . rawurlencode($error)
        : 'saved=' . rawurlencode($saved);
    header('Location: leads.php?' . $query);
    exit;
}

try {
    $leadData = leadStorageRead($storagePath);
} catch (Throwable $exception) {
    error_log('Lead dashboard could not read storage: ' . $exception->getMessage());
    http_response_code(500);
    exit('Could not read data/leads.json. Check the file contents and permissions.');
}

$leads = array_reverse($leadData['leads']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lead Inbox | Floors Gurgaon</title>
    <link rel="stylesheet" href="admin.css">
    <style>
        .leads-wrap{max-width:1600px;margin:0 auto;padding:26px}
        .leads-top,.leads-actions,.lead-actions{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
        .leads-top{margin-bottom:20px}.leads-top h1{margin:8px 0}.leads-top p,.leads-note{color:#667085}
        .leads-back{color:#475467;font-weight:700}.leads-actions{justify-content:flex-end}
        .leads-button{display:inline-flex;align-items:center;justify-content:center;min-height:40px;padding:9px 14px;border:0;border-radius:8px;background:#17233b;color:#fff;font:inherit;font-weight:700;cursor:pointer}
        .leads-button.secondary{background:#edf1f7;color:#344054}.leads-button.danger{background:#fff0f0;color:#b42318}
        .leads-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin:20px 0}
        .leads-stat,.leads-table-wrap{background:#fff;border:1px solid #e8ebf1;border-radius:12px}
        .leads-stat{padding:18px}.leads-stat span{display:block;color:#667085;font-size:12px}.leads-stat strong{display:block;margin-top:5px;font-size:25px}
        .leads-note,.leads-alert{padding:13px 15px;border-radius:9px;margin:14px 0;background:#fff8e8;color:#765516}
        .leads-alert.success{background:#e8f8ee;color:#17663b}.leads-alert.error{background:#fff0f0;color:#a12626}
        .leads-table-wrap{overflow:auto}.leads-table{width:100%;border-collapse:collapse;min-width:1250px}
        .leads-table th,.leads-table td{padding:12px 13px;border-bottom:1px solid #eaecf0;text-align:left;vertical-align:top}
        .leads-table th{background:#f8f9fb;color:#475467;font-size:11px;text-transform:uppercase;letter-spacing:.04em}
        .leads-table td{max-width:260px;overflow-wrap:anywhere}.lead-contact strong,.lead-contact a{display:block}
        .lead-contact a{color:#475467;margin-top:3px}.lead-source{font-weight:750}.lead-muted{color:#667085;font-size:12px}
        .lead-actions{justify-content:flex-start}.lead-delete{border:0;background:#fff0f0;color:#b42318;border-radius:7px;padding:7px 10px;font:inherit;font-weight:700;cursor:pointer}
        .leads-empty{padding:35px;text-align:center;color:#667085}
        @media(max-width:700px){.leads-wrap{padding:15px}.leads-stats{grid-template-columns:1fr}.leads-top{align-items:flex-start}.leads-actions{justify-content:flex-start}}
    </style>
</head>
<body>
<main class="leads-wrap">
    <div class="leads-top">
        <div>
            <a class="leads-back" href="index.php">← Back to dashboard</a>
            <h1>Lead Inbox</h1>
            <p>Review enquiries, lead sources and campaign attribution.</p>
        </div>
        <div class="leads-actions">
            <a class="leads-button secondary" href="leads.php?export=csv">Download Excel CSV</a>
            <form method="post" action="leads.php" onsubmit="return confirm('Delete all currently stored leads? This cannot be undone.');">
                <input type="hidden" name="csrf_token" value="<?= leadsEscape($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="action" value="clear">
                <button class="leads-button danger" type="submit" <?= empty($leads) ? 'disabled' : '' ?>>Delete all stored leads</button>
            </form>
        </div>
    </div>

    <?php if ($saved !== ''): ?><div class="leads-alert success" role="status"><?= leadsEscape($saved) ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="leads-alert error" role="alert"><?= leadsEscape($error) ?></div><?php endif; ?>

    <div class="leads-stats">
        <div class="leads-stat"><span>All-time form submissions</span><strong><?= (int) $leadData['total_submissions'] ?></strong></div>
        <div class="leads-stat"><span>Leads currently stored</span><strong><?= count($leads) ?></strong></div>
        <div class="leads-stat"><span>Submissions since last auto-clean</span><strong><?= (int) $leadData['since_cleanup'] ?></strong></div>
    </div>

    <p class="leads-note">Leads are saved even if email delivery is unavailable. When the 50th stored lead arrives, the lead list is cleared automatically; the all-time submission count remains.</p>

    <section class="leads-table-wrap" aria-label="Saved website leads">
        <?php if (empty($leads)): ?>
            <div class="leads-empty">No leads are currently stored.</div>
        <?php else: ?>
            <table class="leads-table">
                <thead>
                    <tr>
                        <th>Submitted</th><th>Contact</th><th>Property / Form</th>
                        <th>First visit source</th><th>Latest source</th><th>Campaign</th>
                        <th>Landing / submit page</th><th>Message</th><th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leads as $lead): if (!is_array($lead)) continue;
                        $first = is_array($lead['first_touch'] ?? null) ? $lead['first_touch'] : [];
                        $last = is_array($lead['last_touch'] ?? null) ? $lead['last_touch'] : [];
                    ?>
                        <tr>
                            <td><?= leadsEscape($lead['submitted_at'] ?? '') ?></td>
                            <td class="lead-contact">
                                <strong><?= leadsEscape($lead['name'] ?? '') ?></strong>
                                <a href="tel:<?= leadsEscape(preg_replace('/[^0-9+]/', '', (string) ($lead['phone'] ?? ''))) ?>"><?= leadsEscape($lead['phone'] ?? '') ?></a>
                                <a href="mailto:<?= leadsEscape($lead['email'] ?? '') ?>"><?= leadsEscape($lead['email'] ?? '') ?></a>
                            </td>
                            <td><?= leadsEscape($lead['project'] ?? '') ?><div class="lead-muted"><?= leadsEscape($lead['form_id'] ?? '') ?></div></td>
                            <td><span class="lead-source"><?= leadsEscape($first['source'] ?? 'Direct') ?></span><div class="lead-muted"><?= leadsEscape($first['medium'] ?? 'none') ?></div><div class="lead-muted"><?= leadsEscape($first['referrer'] ?? '') ?></div></td>
                            <td><span class="lead-source"><?= leadsEscape($last['source'] ?? 'Direct') ?></span><div class="lead-muted"><?= leadsEscape($last['medium'] ?? 'none') ?></div><div class="lead-muted"><?= leadsEscape($last['referrer'] ?? '') ?></div></td>
                            <td><?= leadsEscape($last['campaign'] ?? 'none') ?><div class="lead-muted">Keyword: <?= leadsEscape($last['keyword'] ?? 'none') ?></div></td>
                            <td><?= leadsEscape($lead['landing_page'] ?? '') ?><div class="lead-muted">Submitted: <?= leadsEscape($lead['submitted_from'] ?? '') ?></div></td>
                            <td><?= leadsEscape($lead['message'] ?? '') ?></td>
                            <td>
                                <form method="post" action="leads.php" onsubmit="return confirm('Delete this lead?');">
                                    <input type="hidden" name="csrf_token" value="<?= leadsEscape($_SESSION['csrf_token']) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="lead_id" value="<?= leadsEscape($lead['id'] ?? '') ?>">
                                    <button class="lead-delete" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
