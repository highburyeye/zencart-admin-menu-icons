<?php
/** @copyright 2026 Tony Cradwick (highburyeye) @license GPL-2.0-only */
if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true || empty($_SESSION['admin_id']) || !empty($GLOBALS['ami_uninstalled'])) {
    return;
}
require_once dirname(__DIR__) . '/classes/AdminMenuIcons.php';
$amiMenuService = new AdminMenuIcons($db);
$amiPayload = $amiMenuService->payload($amiMenuService->menuForUser(), (int)$_SESSION['admin_id']);
?>
<script type="application/json" id="ami-menu-data"><?= json_encode($amiPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE) ?></script>
<?php unset($amiPayload, $amiMenuService); ?>
