<?php
/** Run with an isolated MariaDB and ZENCART_ROOT; see tests/README.md. GPL-2.0-only. */
require __DIR__ . '/bootstrap.php';
seed();
$checks = 0;
function check(bool $value, string $label): void {
    global $checks;
    if (!$value) throw new RuntimeException('FAIL: ' . $label);
    $checks++;
}
function rejects(callable $fn, string $reason): void {
    try { $fn(); } catch (DomainException $e) { check($e->getMessage() === $reason, 'rejection: ' . $reason); return; }
    throw new RuntimeException('Expected rejection: ' . $reason);
}
check($installer->install() === true, 'install');
check((int)freshQuery('SELECT COUNT(*) n FROM ' . TABLE_ADMIN_PAGES_TO_PROFILES . " WHERE page_key='toolsAdminMenuIcons'")->fields['n'] === 3, 'all existing profiles receive only the preferences page');
$queryCache = new QueryCache;
$service = new AdminMenuIcons($db);
$fontCss = file_get_contents($reference . '/admin/includes/fontawesome/css/fontawesome.min.css');
check(array_filter(AdminMenuIconsCatalog::icons(), static fn($name) => !str_contains($fontCss, '.fa-' . $name . ':')) === [], 'every picker icon exists in the host Font Awesome library');
$menu = $service->menuForUser();
check(isset($menu['customers']['orders']) && isset($menu['configuration']['configImages']), 'stable page identifiers survive alphabetically sorted menus');
$token = $_SESSION['securityToken'];
$post = static fn($action, $extra = []) => ['securityToken' => $token, 'ami_action' => $action] + $extra;
$write = static fn($p, $id = 1, $super = true, $visible = null) => $service->dispatch($p, [], $id, $super, $visible ?? $menu, $token);
check(AdminMenuIcons::effectiveIcon('orders', [])['value'] === 'cart-shopping', 'starter icon');
check(AdminMenuIcons::effectiveIcon('configImages', [])['kind'] === 'none', 'sparse defaults');
$write($post('save_icon', ['ami_page' => 'orders', 'ami_kind' => 'none']));
check(AdminMenuIcons::effectiveIcon('orders', $service->assignments())['kind'] === 'none', 'none suppresses default');
$write($post('save_icon', ['ami_page' => 'orders', 'ami_kind' => 'fa', 'ami_value' => 'truck']));
check($service->assignments()['orders']['value'] === 'truck', 'replacement visible despite core query cache');
$write($post('save_icon', ['ami_page' => 'orders', 'ami_kind' => 'default']));
check(AdminMenuIcons::effectiveIcon('orders', $service->assignments())['value'] === 'cart-shopping', 'restore starting icon');
$write($post('favourite', ['ami_page' => 'orders', 'ami_favourite' => '1', 'admin_id' => '3']));
check(isset($service->favourites(1)['orders']) && $service->favourites(3) === [], 'posted identity cannot change another admin');
check($service->favourites(2) === [], 'favourites isolated by user');
$write($post('favourite', ['ami_page' => 'orders', 'ami_favourite' => '0']));
check($service->favourites(1) === [], 'unfavourite');
rejects(fn() => $write(['ami_action' => 'favourite']), 'csrf');
rejects(fn() => $write($post('favourite'), 0), 'forbidden');
rejects(fn() => $write($post('save_icon'), 2, false), 'forbidden');
rejects(fn() => $write($post('upload'), 2, false), 'forbidden');
rejects(fn() => $write($post('delete_image'), 2, false), 'forbidden');
rejects(fn() => $write($post('unknown')), 'action');
rejects(fn() => $write($post('favourite', ['ami_page' => ['orders']])), 'action');
rejects(fn() => $write($post('favourite', ['ami_page' => 'orders', 'ami_favourite' => 'yes'])), 'action');
rejects(fn() => $write($post('save_icon', ['ami_page' => 'missing', 'ami_kind' => 'none'])), 'page');
rejects(fn() => $write($post('save_icon', ['ami_page' => 'orders', 'ami_kind' => 'fa', 'ami_value' => 'star" onclick="alert(1)'])), 'icon');
rejects(fn() => $write($post('save_icon', ['ami_page' => 'orders', 'ami_kind' => 'image', 'ami_value' => '999'])), 'icon');
$_SESSION['admin_id'] = 2; $queryCache = new QueryCache;
$staffMenu = $service->menuForUser();
check(!isset($staffMenu['catalog']), 'core profile permissions respected');
rejects(fn() => $write($post('favourite', ['ami_page' => 'categoriesProductListing', 'ami_favourite' => '1']), 2, false, $staffMenu), 'page');
$write($post('favourite', ['ami_page' => 'orders', 'ami_favourite' => '1']), 2, false, $staffMenu);
check(isset((new AdminMenuIcons($db))->favourites(2)['orders']), 'personal preference survives a new service instance');
rejects(fn() => AdminMenuIcons::normaliseImage('<svg onload="alert(1)"></svg>'), 'image');
rejects(fn() => AdminMenuIcons::normaliseImage(str_repeat('x', 1048577)), 'image_size');
$source = imagecreatetruecolor(200, 100); imagefill($source, 0, 0, imagecolorallocate($source, 25, 125, 135));
ob_start(); imagepng($source); $original = ob_get_clean();
$png = AdminMenuIcons::normaliseImage($original . '<?php echo "should disappear";');
$info = getimagesizefromstring($png);
check($info[0] === 64 && $info[1] === 32 && $info[2] === IMAGETYPE_PNG, 'upload converted to contained 64px PNG');
check(!str_contains($png, '<?php'), 'trailing payload removed by re-encoding');
file_put_contents(__DIR__ . '/icon-test.png', $original);
$db->Execute($db->bindVars('INSERT INTO ' . AdminMenuIcons::tables()['images'] . " (image_label,png_base64) VALUES ('NULL & icon',:data:)", ':data:', base64_encode($png), 'stringIgnoreNull'));
$imageId = (string)$db->Insert_ID();
$write($post('save_icon', ['ami_page' => 'orders', 'ami_kind' => 'image', 'ami_value' => $imageId]));
rejects(fn() => $write($post('delete_image', ['ami_value' => $imageId])), 'in_use');
$payload = $service->payload($staffMenu, 2);
check(count($payload['images']) === 1 && count($payload['entries']) === 3, 'payload contains only permitted menu entries and assigned image');
freshQuery('DELETE FROM ' . TABLE_ADMIN_PAGES_TO_PROFILES . " WHERE profile_id=3 AND page_key='toolsAdminMenuIcons'");
$queryCache = new QueryCache;
check($installer->upgrade() === true, 'upgrade is idempotent');
check(isset($service->favourites(2)['orders']) && isset($service->images()[$imageId]), 'upgrade preserves icons, uploads and favourites');
check((int)freshQuery('SELECT COUNT(*) n FROM ' . TABLE_ADMIN_PAGES_TO_PROFILES . " WHERE profile_id=3 AND page_key='toolsAdminMenuIcons'")->fields['n'] === 0, 'upgrade respects revoked page access');
$write($post('save_icon', ['ami_page' => 'orders', 'ami_kind' => 'none']));
$write($post('delete_image', ['ami_value' => $imageId]));
check($service->images() === [], 'delete unused upload');
check($installer->uninstall() === true, 'uninstall');
check(!empty($GLOBALS['ami_uninstalled']), 'same-request header is suppressed after uninstall');
check((int)freshQuery('SELECT COUNT(*) n FROM ' . TABLE_ADMIN_PAGES_TO_PROFILES . " WHERE page_key='toolsAdminMenuIcons'")->fields['n'] === 0, 'uninstall removes own grants');
check((int)freshQuery('SELECT COUNT(*) n FROM ' . TABLE_ADMIN_PAGES_TO_PROFILES . " WHERE page_key='orders'")->fields['n'] === 1, 'other permissions preserved');
foreach (AdminMenuIcons::tables() as $table) check(freshQuery("SHOW TABLES LIKE '$table'")->RecordCount() === 0, 'own table removed: ' . $table);
// Leave a fresh installed fixture for browser testing.
seed(); $installer->install();
echo "PASS: $checks integration checks, PHP " . PHP_VERSION . ', core ' . $reference . "\n";
