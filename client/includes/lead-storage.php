<?php

function leadStorageDefaultData(): array
{
    return [
        'version' => 1,
        'total_submissions' => 0,
        'since_cleanup' => 0,
        'last_cleanup' => null,
        'leads' => [],
    ];
}

function leadStorageOpenLocked(string $path, int $lockType)
{
    $directory = dirname($path);
    if (!is_dir($directory) || !is_writable($directory)) {
        throw new RuntimeException('Lead storage directory is unavailable.');
    }

    $file = fopen($path, 'c+b');
    if ($file === false) {
        throw new RuntimeException('Could not open lead storage.');
    }

    if (!flock($file, $lockType)) {
        fclose($file);
        throw new RuntimeException('Could not lock lead storage.');
    }

    return $file;
}

function leadStorageDecode($file): array
{
    rewind($file);
    $contents = stream_get_contents($file);

    if ($contents === false) {
        throw new RuntimeException('Could not read lead storage.');
    }

    if (trim($contents) === '') {
        return leadStorageDefaultData();
    }

    $data = json_decode($contents, true);
    if (
        !is_array($data)
        || !isset($data['leads'])
        || !is_array($data['leads'])
        || !isset($data['total_submissions'])
        || !is_numeric($data['total_submissions'])
    ) {
        throw new RuntimeException('Lead storage JSON is invalid.');
    }

    return array_replace(leadStorageDefaultData(), $data);
}

function leadStorageWriteLocked($file, array $data): void
{
    $json = json_encode(
        $data,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    if ($json === false || !rewind($file) || !ftruncate($file, 0)) {
        throw new RuntimeException('Could not prepare lead storage for writing.');
    }

    $contents = $json . PHP_EOL;
    $written = fwrite($file, $contents);

    if ($written !== strlen($contents) || !fflush($file)) {
        throw new RuntimeException('Could not save lead storage.');
    }
}

function leadStorageRead(string $path): array
{
    $file = leadStorageOpenLocked($path, LOCK_SH);

    try {
        return leadStorageDecode($file);
    } finally {
        flock($file, LOCK_UN);
        fclose($file);
    }
}

function leadStorageAppend(string $path, array $lead): array
{
    $file = leadStorageOpenLocked($path, LOCK_EX);

    try {
        $data = leadStorageDecode($file);
        $data['total_submissions'] = (int) $data['total_submissions'] + 1;
        $data['since_cleanup'] = (int) ($data['since_cleanup'] ?? 0) + 1;
        $lead['id'] = bin2hex(random_bytes(12));
        $lead['submitted_at'] = gmdate('Y-m-d H:i:s') . ' UTC';
        $data['leads'][] = $lead;

        $autoCleaned = count($data['leads']) >= 50;
        if ($autoCleaned) {
            $data['leads'] = [];
            $data['since_cleanup'] = 0;
            $data['last_cleanup'] = $lead['submitted_at'];
        }

        leadStorageWriteLocked($file, $data);

        return [
            'lead' => $lead,
            'auto_cleaned' => $autoCleaned,
            'total_submissions' => $data['total_submissions'],
            'stored_leads' => count($data['leads']),
        ];
    } finally {
        flock($file, LOCK_UN);
        fclose($file);
    }
}

function leadStorageDelete(string $path, string $leadId): bool
{
    $file = leadStorageOpenLocked($path, LOCK_EX);

    try {
        $data = leadStorageDecode($file);
        $originalCount = count($data['leads']);
        $data['leads'] = array_values(array_filter(
            $data['leads'],
            static fn ($lead) => !is_array($lead) || ($lead['id'] ?? '') !== $leadId
        ));

        if (count($data['leads']) === $originalCount) {
            return false;
        }

        leadStorageWriteLocked($file, $data);
        return true;
    } finally {
        flock($file, LOCK_UN);
        fclose($file);
    }
}

function leadStorageClear(string $path): int
{
    $file = leadStorageOpenLocked($path, LOCK_EX);

    try {
        $data = leadStorageDecode($file);
        $count = count($data['leads']);
        $data['leads'] = [];
        $data['since_cleanup'] = 0;
        $data['last_cleanup'] = gmdate('Y-m-d H:i:s') . ' UTC';
        leadStorageWriteLocked($file, $data);

        return $count;
    } finally {
        flock($file, LOCK_UN);
        fclose($file);
    }
}
