<?php

function homepageLayoutDefinitions(array $data): array
{
    $definitions = [];
    $projectSections = $data['projectSections'] ?? [];

    if (is_array($projectSections)) {
        foreach ($projectSections as $index => $section) {
            if (!is_array($section) || !is_string($section['id'] ?? null) || $section['id'] === '') {
                continue;
            }

            $sectionId = $section['id'];
            $key = 'project:' . rawurlencode($sectionId);

            if (isset($definitions[$key])) {
                continue;
            }

            $title = trim(implode(' ', array_filter([
                trim((string) ($section['titleHighlight'] ?? '')),
                trim((string) ($section['title'] ?? '')),
            ], static fn ($part) => $part !== '')));

            $definitions[$key] = [
                'type' => 'project',
                'index' => $index,
                'title' => $title !== '' ? $title : 'Property Projects',
                'description' => 'Property cards in this section',
            ];
        }
    }

    $builderSection = $data['builderSection'] ?? [];
    if (!is_array($builderSection)) {
        $builderSection = [];
    }
    $definitions['builders'] = [
        'type' => 'information',
        'title' => trim(implode(' ', array_filter([
            trim((string) ($builderSection['title'] ?? 'Explore Floors by')),
            trim((string) ($builderSection['highlight'] ?? 'Builder')),
        ], static fn ($part) => $part !== ''))),
        'description' => 'Builder directory cards',
    ];

    $locationSection = $data['locationSection'] ?? [];
    if (!is_array($locationSection)) {
        $locationSection = [];
    }
    $definitions['locations'] = [
        'type' => 'information',
        'title' => (string) ($locationSection['title'] ?? 'Prime Locations'),
        'description' => 'Location directory cards',
    ];

    $investmentSection = $data['investmentSection'] ?? [];
    if (!is_array($investmentSection)) {
        $investmentSection = [];
    }
    $definitions['investment'] = [
        'type' => 'information',
        'title' => (string) ($investmentSection['title'] ?? 'Investment Benefits'),
        'description' => 'Investment statistics and benefit cards',
    ];

    return $definitions;
}

function homepageLayoutOrder(array $layout, array $definitions): array
{
    $savedOrder = $layout['order'] ?? [];

    if (!is_array($savedOrder)) {
        $savedOrder = [];
    }

    $order = [];

    foreach ($savedOrder as $key) {
        if ($key === 'projects') {
            foreach ($definitions as $projectKey => $definition) {
                if ($definition['type'] === 'project' && !in_array($projectKey, $order, true)) {
                    $order[] = $projectKey;
                }
            }

            continue;
        }

        if (is_string($key) && isset($definitions[$key]) && !in_array($key, $order, true)) {
            $order[] = $key;
        }
    }

    foreach (array_keys($definitions) as $key) {
        if (!in_array($key, $order, true)) {
            $order[] = $key;
        }
    }

    return $order;
}

function homepageLayoutIsEnabled(array $layout, string $key): bool
{
    $enabled = $layout['enabled'] ?? [];

    if (!is_array($enabled)) {
        return true;
    }

    if (array_key_exists($key, $enabled)) {
        return !empty($enabled[$key]);
    }

    if (strncmp($key, 'project:', 8) === 0 && array_key_exists('projects', $enabled)) {
        return !empty($enabled['projects']);
    }

    return true;
}
