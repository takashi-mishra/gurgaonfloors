<?php

const LEAD_EMAIL_TO_PRIMARY = 'contact@ddjayprojects.com';
const LEAD_EMAIL_TO_SECONDARY = 'hometrust.in@gmail.com';
const LEAD_EMAIL_FROM = 'info@ddjayprojects.com';
const LEAD_EMAIL_SUBJECT = 'New Site Visit Enquiry - JMS Capital Square | Sector 95 Gurgaon';

function saveLeadToDatabase(array $lead, array $tracking): bool
{
    try {
        require __DIR__ . '/function.php';
    } catch (Throwable $error) {
        error_log('Lead database connection failed: ' . $error->getMessage());
        return false;
    }

    $name = (string) ($lead['name'] ?? '');
    $subject = LEAD_EMAIL_SUBJECT;
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $domain = ($isHttps ? 'https://' : 'http://')
        . leadTrackingClean($_SERVER['HTTP_HOST'] ?? '', 255);
    $email = (string) ($lead['email'] ?? '');
    $phone = (string) ($lead['phone'] ?? '');
    $message = (string) ($lead['message'] ?? '');
    $date = date('Y-m-d');
    $status = '0';
    $firstTouch = is_array($tracking['first_touch'] ?? null) ? $tracking['first_touch'] : [];
    $lastTouch = is_array($tracking['last_touch'] ?? null) ? $tracking['last_touch'] : [];
    $source = leadTrackingClean($lastTouch['source'] ?? $firstTouch['source'] ?? 'Direct', 150);
    $medium = leadTrackingClean($lastTouch['medium'] ?? $firstTouch['medium'] ?? 'none', 150);
    $campaign = leadTrackingClean($lastTouch['campaign'] ?? $firstTouch['campaign'] ?? 'none', 150);
    $keyword = leadTrackingClean($lastTouch['keyword'] ?? $firstTouch['keyword'] ?? 'none', 150);
    $referrer = leadTrackingClean($lastTouch['referrer'] ?? $firstTouch['referrer'] ?? 'none', 2000);
    $formId = (string) ($lead['form_id'] ?? '');
    $ip = leadTrackingClean($_SERVER['REMOTE_ADDR'] ?? '', 100);

    $stmt = $mysqli->prepare(
        'INSERT INTO all_query '
        . '(name, form_name, domain, email_id, mobile_no, message, dates, status, '
        . 'lead_source, lead_medium, lead_campaign, lead_keyword, referrer_url, form_id, ip_address) '
        . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    if ($stmt === false) {
        error_log('Lead database insert prepare failed: ' . $mysqli->error);
        $mysqli->close();
        return false;
    }

    $stmt->bind_param(
        'sssssssssssssss',
        $name,
        $subject,
        $domain,
        $email,
        $phone,
        $message,
        $date,
        $status,
        $source,
        $medium,
        $campaign,
        $keyword,
        $referrer,
        $formId,
        $ip
    );
    $saved = $stmt->execute();

    if (!$saved) {
        error_log('Lead database insert failed: ' . $stmt->error);
    }

    $stmt->close();
    $mysqli->close();

    return $saved;
}

function sendLeadEmail(array $lead, array $tracking): array
{
    $escape = static fn ($value): string => htmlspecialchars(
        (string) $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
    $firstTouch = is_array($tracking['first_touch'] ?? null)
        ? $tracking['first_touch']
        : [];
    $lastTouch = is_array($tracking['last_touch'] ?? null)
        ? $tracking['last_touch']
        : [];
    $trackingValue = static function (array $source, string $key): string {
        return leadTrackingClean($source[$key] ?? 'none', 2000);
    };

    $rows = [
        'Full Name' => $lead['name'] ?? '',
        'Phone No' => $lead['phone'] ?? '',
        'Email ID' => $lead['email'] ?? '',
        'Property / Project' => $lead['project'] ?? '',
        'Message' => ($lead['message'] ?? '') !== '' ? $lead['message'] : 'Not provided',
        'Consent' => !empty($lead['consent']) ? 'Yes' : 'No',
        'Subject' => LEAD_EMAIL_SUBJECT,
        'Form ID' => $lead['form_id'] ?? '',
        'Received' => date('d M Y, h:i A T'),
        'IP Address' => leadTrackingClean($_SERVER['REMOTE_ADDR'] ?? 'Unavailable', 100),
        'Lead Source' => $trackingValue($firstTouch, 'source'),
        'Lead Medium' => $trackingValue($firstTouch, 'medium'),
        'Campaign' => $trackingValue($firstTouch, 'campaign'),
        'Keyword' => $trackingValue($firstTouch, 'keyword'),
        'Referrer' => $trackingValue($firstTouch, 'referrer'),
        'Latest Source' => $trackingValue($lastTouch, 'source'),
        'Latest Medium' => $trackingValue($lastTouch, 'medium'),
        'Latest Campaign' => $trackingValue($lastTouch, 'campaign'),
        'Latest Keyword' => $trackingValue($lastTouch, 'keyword'),
        'Latest Referrer' => $trackingValue($lastTouch, 'referrer'),
        'Landing Page' => $tracking['landing_page'] ?? '/',
        'Submitted From' => $tracking['current_page'] ?? '/',
        'Domain' => leadTrackingClean($_SERVER['HTTP_HOST'] ?? '', 255),
    ];

    $tableRows = '';
    foreach ($rows as $label => $value) {
        $tableRows .= '<tr><td style="padding:10px;border:1px solid #e5e7eb;font-weight:bold;">'
            . $escape($label)
            . '</td><td style="padding:10px;border:1px solid #e5e7eb;">'
            . $escape($value)
            . '</td></tr>';
    }

    $htmlBody = '<!doctype html><html lang="en"><head><meta charset="UTF-8">'
        . '<title>New Site Visit Enquiry</title></head>'
        . '<body style="font-family:Arial,sans-serif;color:#333;">'
        . '<h2>New Website Enquiry - Floors Gurgaon</h2>'
        . '<table cellspacing="0" cellpadding="0" style="border-collapse:collapse;width:100%;max-width:700px;">'
        . $tableRows
        . '</table></body></html>';

    $headers = [
        'From: JMS Retail Shops Enquiry <' . LEAD_EMAIL_FROM . '>',
        'Cc: ' . LEAD_EMAIL_TO_SECONDARY,
        'Reply-To: ' . $lead['email'],
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'X-Mailer: PHP/' . PHP_VERSION,
    ];

    return [
        'database_saved' => saveLeadToDatabase($lead, $tracking),
        'mail_sent' => mail(
        LEAD_EMAIL_TO_PRIMARY,
        LEAD_EMAIL_SUBJECT,
        $htmlBody,
        implode("\r\n", $headers)
        ),
    ];
}
