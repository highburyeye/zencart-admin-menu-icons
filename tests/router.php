<?php
/** Isolated local development fixture. Never install tests on a shop. GPL-2.0-only. */
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$assetRoots = [
    '/shop/zc_plugins/' => dirname(__DIR__) . '/zc_plugins/',
    '/shop/secret-admin/core/' => rtrim(getenv('ZENCART_ROOT'), '/') . '/admin/includes/',
];
foreach ($assetRoots as $prefix => $root) {
    if (!str_starts_with($path, $prefix)) continue;
    $file = realpath($root . substr($path, strlen($prefix)));
    if (!$file || !str_starts_with($file, realpath($root) . '/') || !is_file($file)) { http_response_code(404); exit; }
    $mime = ['css' => 'text/css', 'js' => 'text/javascript', 'woff2' => 'font/woff2', 'woff' => 'font/woff', 'ttf' => 'font/ttf'];
    $ext = pathinfo($file, PATHINFO_EXTENSION);
    if (!isset($mime[$ext])) { http_response_code(404); exit; }
    header('Content-Type: ' . $mime[$ext]); readfile($file); exit;
}
require __DIR__ . '/bootstrap.php';
if (isset($_GET['user'])) $_SESSION['admin_id'] = (int)$_GET['user'];
$PHP_SELF = 'admin_menu_icons.php';
require $reference . '/admin/includes/classes/AdminRequestSanitizer.php';
require $pluginRoot . '/admin/includes/init_includes/init_admin_menu_icons_sanitizers.php';
require $reference . '/admin/includes/init_includes/init_sanitize.php';
define('DIR_WS_INCLUDES', __DIR__ . '/fixture_env/includes/');
set_include_path(__DIR__ . '/fixture_env' . PATH_SEPARATOR . get_include_path());
$messageStack = new class {
    public function add_session($message, $type) { $_SESSION['fixture_notice'] = $message; }
};
require $pluginRoot . '/admin/admin_menu_icons.php';
