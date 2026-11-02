<?php

function leadTrackingClean($value, int $maxLength = 200): string
{
    if (!is_string($value) && !is_numeric($value)) {
        return '';
    }

    $value = trim(strip_tags((string) $value));
    $value = preg_replace('/[\x00-\x1F\x7F]/', ' ', $value) ?? '';

    return substr($value, 0, $maxLength);
}

function leadTrackingRequestAttribution(): array
{
    $referrer = leadTrackingClean($_SERVER['HTTP_REFERER'] ?? '', 2000);
    $referrerParts = $referrer !== '' ? parse_url($referrer) : false;
    $referrerHost = is_array($referrerParts)
        ? strtolower((string) ($referrerParts['host'] ?? ''))
        : '';
    $referrerScheme = is_array($referrerParts)
        ? strtolower((string) ($referrerParts['scheme'] ?? ''))
        : '';
    $currentHost = strtolower(leadTrackingClean($_SERVER['HTTP_HOST'] ?? '', 255));

    if (
        !in_array($referrerScheme, ['http', 'https'], true)
        || $referrerHost === ''
        || $referrerHost === $currentHost
    ) {
        $referrer = '';
        $referrerHost = '';
    } else {
        $referrerPath = leadTrackingClean($referrerParts['path'] ?? '', 500);
        $referrer = $referrerScheme . '://' . $referrerHost . $referrerPath;
    }

    $utmSource = leadTrackingClean($_GET['utm_source'] ?? '', 150);
    $utmMedium = leadTrackingClean($_GET['utm_medium'] ?? '', 150);
    $utmCampaign = leadTrackingClean($_GET['utm_campaign'] ?? '', 150);
    $utmTerm = leadTrackingClean($_GET['utm_term'] ?? '', 150);

    if ($utmSource !== '' || $utmMedium !== '' || $utmCampaign !== '' || $utmTerm !== '') {
        return [
            'source' => $utmSource !== '' ? $utmSource : 'Campaign',
            'medium' => $utmMedium !== '' ? $utmMedium : 'unknown',
            'campaign' => $utmCampaign !== '' ? $utmCampaign : 'none',
            'keyword' => $utmTerm !== '' ? $utmTerm : 'none',
            'referrer' => $referrer !== '' ? $referrer : 'none',
        ];
    }

    if (!empty($_GET['gclid']) || !empty($_GET['gbraid']) || !empty($_GET['wbraid'])) {
        return [
            'source' => 'Google Ads',
            'medium' => 'cpc',
            'campaign' => 'google_ads',
            'keyword' => 'none',
            'referrer' => $referrer !== '' ? $referrer : 'none',
        ];
    }

    if (!empty($_GET['fbclid'])) {
        return [
            'source' => 'Facebook Ads',
            'medium' => 'cpc',
            'campaign' => 'facebook_ads',
            'keyword' => 'none',
            'referrer' => $referrer !== '' ? $referrer : 'none',
        ];
    }

    if ($referrerHost === '') {
        return [
            'source' => 'Direct',
            'medium' => 'none',
            'campaign' => 'none',
            'keyword' => 'none',
            'referrer' => 'none',
        ];
    }

    $source = $referrerHost;
    $medium = 'referral';

    if (preg_match('/(^|\.)google\.[a-z.]+$/i', $referrerHost)) {
        $source = 'Google';
        $medium = 'organic';
    } elseif (preg_match('/(^|\.)facebook\.com$/i', $referrerHost) || preg_match('/(^|\.)fb\.com$/i', $referrerHost)) {
        $source = 'Facebook';
        $medium = 'social';
    } elseif (preg_match('/(^|\.)instagram\.com$/i', $referrerHost)) {
        $source = 'Instagram';
        $medium = 'social';
    } elseif (preg_match('/(^|\.)linkedin\.com$/i', $referrerHost)) {
        $source = 'LinkedIn';
        $medium = 'social';
    }

    return [
        'source' => $source,
        'medium' => $medium,
        'campaign' => 'none',
        'keyword' => 'none',
        'referrer' => $referrer,
    ];
}

function leadTrackingInitialize(): array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $currentAttribution = leadTrackingRequestAttribution();
    $isDirect = $currentAttribution['source'] === 'Direct';

    if (empty($_SESSION['lead_first_touch'])) {
        $_SESSION['lead_first_touch'] = $currentAttribution;
        $_SESSION['lead_landing_page'] = leadTrackingClean(
            parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/',
            500
        );
    }

    if (!$isDirect || empty($_SESSION['lead_last_touch'])) {
        $_SESSION['lead_last_touch'] = $currentAttribution;
    }

    return [
        'first_touch' => $_SESSION['lead_first_touch'],
        'last_touch' => $_SESSION['lead_last_touch'],
        'landing_page' => $_SESSION['lead_landing_page'] ?? '/',
        'current_page' => leadTrackingClean(
            parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/',
            500
        ),
    ];
}

function leadTrackingCsrfToken(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (empty($_SESSION['lead_csrf_token'])) {
        $_SESSION['lead_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['lead_csrf_token'];
}
