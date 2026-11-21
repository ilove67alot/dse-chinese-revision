<?php
declare(strict_types=1);

function env_first(string ...$names): ?string
{
    foreach ($names as $name) {
        $value = getenv($name);
        if ($value !== false && trim($value) !== '') {
            return trim($value);
        }
    }
    return null;
}

function supabase_config(): ?array
{
    static $config = false;
    if ($config === false) {
        $url = env_first('SUPABASE_URL', 'NEXT_PUBLIC_SUPABASE_URL');
        $key = env_first('SUPABASE_ANON_KEY', 'SUPABASE_PUBLISHABLE_KEY', 'NEXT_PUBLIC_SUPABASE_ANON_KEY');
        $config = ($url && $key) ? ['url' => rtrim($url, '/'), 'key' => $key] : null;
    }
    return $config;
}

function supabase_enabled(): bool
{
    return supabase_config() !== null;
}

/**
 * Calls the Supabase REST API (PostgREST). Returns decoded JSON, or throws on failure.
 */
function supabase_request(string $method, string $table, array $query = [], ?array $body = null, array $extraHeaders = []): mixed
{
    $config = supabase_config();
    if ($config === null) {
        throw new RuntimeException('Supabase is not configured.');
    }

    $url = $config['url'] . '/rest/v1/' . rawurlencode($table);
    if ($query) {
        $url .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    $headers = [
        'apikey: ' . $config['key'],
        'Accept: application/json',
        'Content-Type: application/json',
    ];
    // Legacy anon keys are JWTs and go in Authorization too; new sb_publishable_ keys must not.
    if (str_starts_with($config['key'], 'eyJ')) {
        $headers[] = 'Authorization: Bearer ' . $config['key'];
    }
    $headers = array_merge($headers, $extraHeaders);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 10,
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
    }

    $response = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        throw new RuntimeException('Supabase request failed: ' . $error);
    }
    if ($status >= 400) {
        throw new RuntimeException("Supabase returned HTTP $status: " . substr((string) $response, 0, 300));
    }
    if ($response === '') {
        return null;
    }
    return json_decode((string) $response, true, 512, JSON_THROW_ON_ERROR);
}

/** Fetches every row of a table, paging past PostgREST's max-rows limit. */
function supabase_select_all(string $table, string $select, string $order): array
{
    $rows = [];
    while (true) {
        $page = supabase_request('GET', $table, [
            'select' => $select,
            'order' => $order,
            'limit' => 1000,
            'offset' => count($rows),
        ]);
        if (!$page) {
            return $rows;
        }
        $rows = array_merge($rows, $page);
    }
}
