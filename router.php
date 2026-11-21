<?php
// Local dev router: php -S localhost:8000 router.php
// Mirrors the routes in vercel.json.
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';

if (str_starts_with($path, '/assets/') && is_file(__DIR__ . $path)) {
    return false;
}

if ($path === '/' || $path === '/index.php') {
    $page = 'index';
} elseif (preg_match('#^/(fanwen|flashcards|quiz|practice|writing)(?:\.php)?/?$#', $path, $m)) {
    $page = $m[1];
} else {
    http_response_code(404);
    $page = null;
}

if ($page === null) {
    require __DIR__ . '/api/_lib/layout.php';
    render_header('找不到頁面', '');
    echo '<h1 class="page-title">找不到頁面</h1><p><a href="/">返回首頁</a></p>';
    render_footer();
    return true;
}

$_SERVER['SCRIPT_NAME'] = "/api/$page.php";
require __DIR__ . "/api/$page.php";
