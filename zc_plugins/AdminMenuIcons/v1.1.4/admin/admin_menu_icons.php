<?php
/** @copyright 2026 Tony Cradwick (highburyeye) @license GPL-2.0-only */
if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true) {
    exit;
}
require 'includes/application_top.php';
if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true || empty($_SESSION['admin_id'])) {
    http_response_code(403);
    exit;
}
require_once __DIR__ . '/includes/classes/AdminMenuIcons.php';
header('Cache-Control: no-store');
$amiService = new AdminMenuIcons($db);
$amiMenu = $amiService->menuForUser();
$amiSuperuser = zen_is_superuser();
$amiError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amiJson = ($_POST['ami_response'] ?? '') === 'json';
    try {
        $amiResult = $amiService->dispatch($_POST, $_FILES, (int)$_SESSION['admin_id'], $amiSuperuser, $amiMenu, (string)($_SESSION['securityToken'] ?? ''));
        if (($_POST['ami_action'] ?? '') !== 'favourite') {
            zen_record_admin_activity('Admin Menu Icons: ' . $_POST['ami_action'] . ' ' . $amiResult['key'], 'info');
        }
        if ($amiJson) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['ok' => true] + $amiResult);
            exit;
        }
        $messageStack->add_session(AMI_SAVED, 'success');
        $amiRedirect = zen_href_link(FILENAME_ADMIN_MENU_ICONS);
        if ($amiResult['key'] !== '') {
            $amiRedirect .= '#ami-row-' . rawurlencode($amiResult['key']);
        }
        zen_redirect($amiRedirect);
        exit;
    } catch (DomainException $e) {
        $amiErrorKey = 'AMI_ERROR_' . strtoupper($e->getMessage());
        $amiError = defined($amiErrorKey) ? constant($amiErrorKey) : AMI_ERROR_ACTION;
        if ($amiJson) {
            http_response_code(in_array($e->getMessage(), ['csrf', 'forbidden'], true) ? 403 : 422);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['ok' => false, 'error' => $amiError]);
            exit;
        }
    }
}
$amiAssignments = $amiService->assignments();
$amiFavourites = $amiService->favourites((int)$_SESSION['admin_id']);
$amiImages = $amiService->images();
$amiTitles = zen_get_menu_titles();
$amiEnabled = $amiService->enabled();
$amiUploadTypes = AdminMenuIcons::uploadTypes();
$amiUploadLimit = AdminMenuIcons::uploadLimit();
$amiUploadHelp = $amiUploadTypes === [] ? AMI_ERROR_GD : sprintf(
    AMI_UPLOAD_HELP,
    implode(', ', array_column($amiUploadTypes, 'label')),
    rtrim(rtrim(number_format($amiUploadLimit / 1048576, 2, '.', ''), '0'), '.'),
    AdminMenuIcons::MAX_DIMENSION
);
$amiEscape = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$amiRenderIcon = static function (array $icon) use ($amiImages, $amiEscape): string {
    if ($icon['kind'] === 'fa' && in_array($icon['value'], AdminMenuIconsCatalog::icons(), true)) {
        return '<i class="fa-solid fa-' . $amiEscape($icon['value']) . '" aria-hidden="true"></i>';
    }
    if ($icon['kind'] === 'image' && isset($amiImages[$icon['value']])) {
        return '<img src="' . $amiEscape($amiImages[$icon['value']]['src']) . '" alt="" width="16" height="16">';
    }
    return '';
};
require __DIR__ . '/includes/admin_menu_icons_view.php';
require 'includes/application_bottom.php';
