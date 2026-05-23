<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=900');

$userId = getenv('INSTAGRAM_USER_ID') ?: '';
$accessToken = getenv('INSTAGRAM_ACCESS_TOKEN') ?: '';
$apiBase = rtrim(getenv('INSTAGRAM_API_BASE') ?: 'https://graph.instagram.com', '/');
$cacheFile = __DIR__ . '/instagram-feed-cache.json';
$cacheTtl = 900;

function send_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTtl) {
    $cached = file_get_contents($cacheFile);
    if ($cached !== false) {
        echo $cached;
        exit;
    }
}

if ($userId === '' || $accessToken === '') {
    send_json([
        'items' => [],
        'error' => 'Instagram feed is not configured.',
    ], 503);
}

$fields = 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp';
$endpoint = sprintf(
    '%s/%s/media?fields=%s&limit=3&access_token=%s',
    $apiBase,
    rawurlencode($userId),
    rawurlencode($fields),
    rawurlencode($accessToken)
);

$ch = curl_init($endpoint);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 8,
    CURLOPT_TIMEOUT => 12,
    CURLOPT_FAILONERROR => false,
]);

$raw = curl_exec($ch);
$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($raw === false || $httpCode < 200 || $httpCode >= 300) {
    send_json([
        'items' => [],
        'error' => $curlError !== '' ? $curlError : 'Instagram API request failed.',
    ], 502);
}

$decoded = json_decode($raw, true);
if (!is_array($decoded) || !isset($decoded['data']) || !is_array($decoded['data'])) {
    send_json([
        'items' => [],
        'error' => 'Instagram API returned an unexpected response.',
    ], 502);
}

$items = array_map(static function (array $post): array {
    $image = $post['thumbnail_url'] ?? $post['media_url'] ?? '';

    return [
        'id' => $post['id'] ?? '',
        'caption' => $post['caption'] ?? '',
        'image' => $image,
        'link' => $post['permalink'] ?? 'https://www.instagram.com/sbeltbeauty/',
        'timestamp' => $post['timestamp'] ?? '',
        'media_type' => $post['media_type'] ?? '',
    ];
}, $decoded['data']);

$payload = [
    'items' => array_values(array_filter($items, static fn (array $item): bool => $item['image'] !== '')),
    'source' => 'instagram',
];

$json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
if ($json === false) {
    send_json(['items' => [], 'error' => 'Could not encode Instagram feed.'], 500);
}

file_put_contents($cacheFile, $json, LOCK_EX);
echo $json;
