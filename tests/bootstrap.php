<?php
/** Local test harness only; never part of the install ZIP. GPL-2.0-only. */
error_reporting(E_ALL);
set_error_handler(static function ($level, $message, $file, $line) {
    if (error_reporting() & $level) throw new ErrorException($message, 0, $level, $file, $line);
    return false;
});
$reference = realpath(getenv('ZENCART_ROOT') ?: '');
if (!$reference || !is_file($reference . '/includes/classes/db/mysql/query_factory.php')) {
    throw new RuntimeException('Set ZENCART_ROOT to a clean Zen Cart 2.1.0 or 2.2.1 checkout.');
}
if (getenv('AMI_TEST_DATABASE') !== 'ami_menu_icons_test') {
    throw new RuntimeException('Set AMI_TEST_DATABASE=ami_menu_icons_test; tests destroy only this isolated test database.');
}
define('IS_ADMIN_FLAG', true);
define('DB_PREFIX', 'ami_test_');
define('DB_CHARSET', 'utf8mb4');
define('DB_SOCKET', getenv('AMI_TEST_SOCKET') ?: '');
define('DB_PORT', (int)(getenv('AMI_TEST_PORT') ?: 33316));
define('DIR_FS_CATALOG', $reference . '/');
define('DIR_WS_CLASSES', 'includes/classes/');
define('HTTP_SERVER', 'http://127.0.0.1:8765');
define('DIR_WS_ADMIN', '/shop/secret-admin/');
define('SUPERUSER_PROFILE', 1);
define('CHARSET', 'UTF-8');
define('HTML_PARAMS', 'lang="en"');
define('HEADER_TOGGLE_NAVIGATION', 'Toggle navigation');
define('FILENAME_ALT_NAV', 'alt_nav');
define('FILENAME_DEFAULT', 'home');
define('BOX_HEADING_PRODUCT_TYPES', 'Product types');
define('MENU_CATEGORIES_TO_SORT_BY_NAME', 'configuration,catalog,customers,tools');
define('MODULE_ORDER_TOTAL_COUPON_STATUS', 'true');
define('PLUGIN_INSTALL_SQL_FAILURE', 'SQL installation failed');
foreach (['ADMIN', 'ADMIN_PROFILES', 'ADMIN_PAGES', 'ADMIN_MENUS', 'ADMIN_PAGES_TO_PROFILES', 'PRODUCT_TYPES'] as $name) {
    define('TABLE_' . $name, DB_PREFIX . strtolower($name));
}
function zen_define_default($key, $value) { if (!defined($key)) define($key, $value); }
function zen_record_admin_activity($message, $severity = 'info') { $GLOBALS['activity'][] = [$message, $severity]; }
function zen_output_string($value, $translate = false, $protected = false) { return htmlspecialchars((string)$value, ENT_COMPAT, 'UTF-8', false); }
function page_has_help() { return false; }
function zen_redirect($url) { header('Location: ' . html_entity_decode($url, ENT_QUOTES, 'UTF-8')); exit; }
require $reference . '/includes/classes/vendors/AuraAutoload/src/Loader.php';
$psr4Autoloader = new Aura\Autoload\Loader;
$psr4Autoloader->register();
require $reference . '/includes/psr4Autoload.php';
require $reference . '/includes/classes/class.base.php';
require $reference . '/includes/classes/db/mysql/query_factory.php';
require $reference . '/includes/classes/query_cache.php';
require $reference . '/admin/includes/functions/admin_access.php';
require $reference . '/admin/includes/functions/html_output.php';
$zco_notifier = new base;
$queryCache = new QueryCache;
$session_started = true;
$amiTestHost = getenv('AMI_TEST_HOST') ?: '127.0.0.1';
$connection = new mysqli($amiTestHost, 'root', '', '', DB_PORT, DB_SOCKET);
$connection->query('CREATE DATABASE IF NOT EXISTS ami_menu_icons_test CHARACTER SET utf8mb4');
$connection->close();
$db = new queryFactory;
if (!$db->connect($amiTestHost, 'root', '', 'ami_menu_icons_test', 'unused', false)) throw new RuntimeException('Test database connection failed.');
$pluginRoot = dirname(__DIR__) . '/zc_plugins/AdminMenuIcons/v1.1.0';
require $pluginRoot . '/admin/includes/extra_datafiles/admin_menu_icons_filenames.php';
foreach ([$pluginRoot . '/admin/includes/languages/english/extra_definitions/lang.admin_menu_icons_name.php', $pluginRoot . '/admin/includes/languages/english/lang.admin_menu_icons.php'] as $file) {
    foreach (require $file as $key => $value) define($key, $value);
}
require $pluginRoot . '/admin/includes/classes/AdminMenuIcons.php';
require $pluginRoot . '/Installer/ScriptedInstaller.php';
class AmiTestInstaller extends ScriptedInstaller
{
    public function install() { return $this->executeInstall(); }
    public function upgrade() { return $this->executeUpgrade('v1.0.0'); }
    public function uninstall() { return $this->executeUninstall(); }
}
function freshQuery(string $sql) { return $GLOBALS['db']->Execute($sql, null, false, 0, true); }
function defineFixtureLanguage(): void
{
    foreach ([
        'AMI_TEST_CONFIGURATION' => 'Configuration', 'AMI_TEST_CATALOG' => 'Catalog', 'AMI_TEST_CUSTOMERS' => 'Customers', 'AMI_TEST_TOOLS' => 'Tools',
        'AMI_TEST_ORDERS' => 'Orders', 'AMI_TEST_PRODUCTS' => 'Categories / Products', 'AMI_TEST_IMAGES' => 'Images',
        'AMI_TEST_STORE' => 'My Store', 'AMI_TEST_LOGS' => 'Display Log Files', 'AMI_TEST_CUSTOM_PAGE' => 'Third-party & tools',
        'AMI_TEST_FILE_ORDERS' => 'orders', 'AMI_TEST_FILE_PRODUCTS' => 'category_product_listing',
        'AMI_TEST_FILE_CONFIGURATION' => 'configuration', 'AMI_TEST_FILE_CUSTOMERS' => 'customers', 'AMI_TEST_FILE_LOGS' => 'display_logs',
        'AMI_TEST_FILE_THIRD' => 'third_party',
    ] as $key => $value) zen_define_default($key, $value);
}
defineFixtureLanguage();
function seed(): void
{
    global $db, $queryCache;
    foreach (array_merge(AdminMenuIcons::tables(), [TABLE_ADMIN, TABLE_ADMIN_PROFILES, TABLE_ADMIN_PAGES, TABLE_ADMIN_MENUS, TABLE_ADMIN_PAGES_TO_PROFILES, TABLE_PRODUCT_TYPES]) as $table) {
        freshQuery('DROP TABLE IF EXISTS ' . $table);
    }
    $queryCache = new QueryCache;
    freshQuery('CREATE TABLE ' . TABLE_ADMIN . ' (admin_id INT PRIMARY KEY, admin_profile INT)');
    freshQuery('CREATE TABLE ' . TABLE_ADMIN_PROFILES . ' (profile_id INT PRIMARY KEY, profile_name VARCHAR(100))');
    freshQuery('CREATE TABLE ' . TABLE_ADMIN_MENUS . ' (menu_key VARCHAR(64), language_key VARCHAR(100), sort_order INT)');
    freshQuery('CREATE TABLE ' . TABLE_ADMIN_PAGES . ' (page_key VARCHAR(64) PRIMARY KEY, language_key VARCHAR(100), main_page VARCHAR(100), page_params VARCHAR(100), menu_key VARCHAR(64), display_on_menu CHAR(1), sort_order INT)');
    freshQuery('CREATE TABLE ' . TABLE_ADMIN_PAGES_TO_PROFILES . ' (profile_id INT, page_key VARCHAR(64), PRIMARY KEY(profile_id, page_key))');
    freshQuery('CREATE TABLE ' . TABLE_PRODUCT_TYPES . ' (type_handler VARCHAR(100), type_name VARCHAR(100))');
    freshQuery('INSERT INTO ' . TABLE_ADMIN . ' VALUES (1,1),(2,2),(3,3)');
    freshQuery("INSERT INTO " . TABLE_ADMIN_PROFILES . " VALUES (1,'Superadmin'),(2,'Orders staff'),(3,'Other staff')");
    foreach (['configuration', 'catalog', 'customers', 'tools'] as $i => $menu) {
        freshQuery("INSERT INTO " . TABLE_ADMIN_MENUS . " VALUES ('$menu','AMI_TEST_" . strtoupper($menu) . "',$i)");
    }
    zen_register_admin_page('configMyStore', 'AMI_TEST_STORE', 'AMI_TEST_FILE_CONFIGURATION', 'gID=1', 'configuration', 'Y', 1);
    zen_register_admin_page('configImages', 'AMI_TEST_IMAGES', 'AMI_TEST_FILE_CONFIGURATION', 'gID=4', 'configuration', 'Y', 2);
    zen_register_admin_page('categoriesProductListing', 'AMI_TEST_PRODUCTS', 'AMI_TEST_FILE_PRODUCTS', '', 'catalog', 'Y', 1);
    zen_register_admin_page('customers', 'AMI_TEST_CUSTOMERS', 'AMI_TEST_FILE_CUSTOMERS', '', 'customers', 'Y', 1);
    zen_register_admin_page('orders', 'AMI_TEST_ORDERS', 'AMI_TEST_FILE_ORDERS', '', 'customers', 'Y', 2);
    zen_register_admin_page('toolsDisplayLogs', 'AMI_TEST_LOGS', 'AMI_TEST_FILE_LOGS', '', 'tools', 'Y', 1);
    zen_register_admin_page('thirdParty', 'AMI_TEST_CUSTOM_PAGE', 'AMI_TEST_FILE_THIRD', 'mode=list', 'tools', 'Y', 2);
    freshQuery("INSERT INTO " . TABLE_ADMIN_PAGES_TO_PROFILES . " VALUES (2,'orders'),(2,'customers'),(3,'customers')");
    $queryCache = new QueryCache;
    unset($GLOBALS['ami_uninstalled']);
}
if (PHP_SAPI !== 'cli') {
    session_save_path(sys_get_temp_dir());
    session_start();
}
$_SESSION['admin_id'] = $_SESSION['admin_id'] ?? 1;
$_SESSION['securityToken'] = 'ami-test-csrf-token';
$installer = new AmiTestInstaller($db, new Zencart\PluginSupport\PluginErrorContainer);
