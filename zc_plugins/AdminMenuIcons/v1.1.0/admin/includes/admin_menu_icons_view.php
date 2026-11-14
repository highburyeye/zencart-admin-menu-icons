<?php
/** @copyright 2026 Tony Cradwick (highburyeye) @license GPL-2.0-only */
if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true) {
    exit;
}
?>
<!doctype html>
<html <?= HTML_PARAMS ?>>
<head><?php require DIR_WS_INCLUDES . 'admin_html_head.php'; ?></head>
<body>
<?php require DIR_WS_INCLUDES . 'header.php'; ?>
<main class="container-fluid ami-settings" id="ami-settings" data-network-error="<?= $amiEscape(AMI_NETWORK_ERROR) ?>">
    <header class="ami-heading">
        <span class="ami-heading-symbol"><i class="fa-solid fa-palette" aria-hidden="true"></i></span>
        <div><h1><?= AMI_HEADING ?></h1><p><?= AMI_INTRO ?></p></div>
    </header>
    <section class="ami-master-panel" aria-labelledby="ami-master-title">
        <div><strong id="ami-master-title"><?= AMI_MASTER_TITLE ?></strong><p id="ami-master-help"><?= AMI_MASTER_HELP ?></p></div>
        <div class="ami-master-control"><strong id="ami-master-state"><?= $amiEnabled ? AMI_ON : AMI_OFF ?></strong>
        <?php if ($amiSuperuser) { ?>
            <?= zen_draw_form('ami_master', FILENAME_ADMIN_MENU_ICONS, '', 'post', 'id="ami-master-form"') ?>
                <input type="hidden" name="ami_action" value="set_enabled"><input type="hidden" name="ami_enabled" value="<?= $amiEnabled ? '0' : '1' ?>">
                <button type="submit" id="ami-master-switch" class="ami-switch" role="switch" aria-checked="<?= $amiEnabled ? 'true' : 'false' ?>" aria-label="<?= $amiEscape(AMI_MASTER_LABEL) ?>" aria-describedby="ami-master-help"><span></span></button>
            </form>
        <?php } ?>
        </div>
    </section>
    <?php if (!$amiEnabled) { ?><p id="ami-off-notice" class="alert alert-warning"><?= AMI_OFF_NOTE ?></p><?php } ?>
    <div class="ami-notes"><p><?= AMI_SHARED_NOTE ?></p><p><?= AMI_PERSONAL_NOTE ?></p></div>
    <noscript><p class="alert alert-warning"><?= AMI_JS_NOTE ?></p></noscript>
    <p id="ami-status" role="status" aria-live="polite" class="<?= $amiError !== '' ? 'alert alert-danger' : '' ?>"><?= $amiEscape($amiError) ?></p>
    <div class="ami-filters">
        <label class="ami-search-label" for="ami-search"><?= AMI_SEARCH ?><input id="ami-search" class="form-control" type="search" placeholder="<?= $amiEscape(AMI_SEARCH_PLACEHOLDER) ?>"></label>
        <label for="ami-group"><?= AMI_MENU ?><select id="ami-group" class="form-control"><option value=""><?= AMI_ALL_MENUS ?></option>
            <?php foreach ($amiMenu as $group => $pages) { ?><option value="<?= $amiEscape($group) ?>"><?= $amiEscape($amiTitles[$group] ?? $group) ?></option><?php } ?>
        </select></label>
        <label class="ami-only-label"><input id="ami-only-favourites" type="checkbox" class="ami-filter-switch"> <?= AMI_FAVOURITES_ONLY ?></label>
    </div>
    <div class="table-responsive">
        <table class="table ami-table">
            <thead><tr><th scope="col"><?= AMI_ENTRY ?></th><th scope="col"><?= AMI_SHARED_ICON ?></th><th scope="col" class="ami-favourite-column"><?= AMI_MY_FAVOURITE ?></th></tr></thead>
            <?php foreach ($amiMenu as $group => $pages) { ?>
            <tbody class="ami-group" data-group="<?= $amiEscape($group) ?>">
                <tr class="ami-group-heading"><th colspan="3" scope="rowgroup"><?= $amiEscape($amiTitles[$group] ?? $group) ?></th></tr>
                <?php foreach ($pages as $key => $page) {
                    $icon = AdminMenuIcons::effectiveIcon((string)$key, $amiAssignments);
                    $favourite = isset($amiFavourites[$key]);
                    $name = trim(strip_tags($page['name']));
                    $iconName = $icon['kind'] === 'none' ? AMI_NONE : ($icon['kind'] === 'image' ? ($amiImages[$icon['value']]['label'] ?? AMI_NONE) : str_replace('-', ' ', $icon['value']));
                ?>
                <tr class="ami-row" id="ami-row-<?= $amiEscape($key) ?>" data-key="<?= $amiEscape($key) ?>" data-name="<?= $amiEscape($name) ?>" data-favourite="<?= $favourite ? '1' : '0' ?>">
                    <td><span class="ami-preview"><span class="ami-icon-slot" aria-hidden="true"><?= $amiRenderIcon($icon) ?></span><span class="ami-entry-name <?= $favourite ? 'ami-is-favourite' : '' ?>"><?= $amiEscape($name) ?></span></span></td>
                    <td><div class="ami-assignment"><span class="ami-icon-name"><?= $amiEscape($iconName) ?><?php if (!isset($amiAssignments[$key])) { ?> <small class="ami-default-badge"><?= AMI_DEFAULT_BADGE ?></small><?php } ?></span>
                    <?php if ($amiSuperuser) { ?><button type="button" class="btn btn-default btn-sm ami-change" data-key="<?= $amiEscape($key) ?>"><?= AMI_CHANGE ?></button><?php } ?></div></td>
                    <td class="ami-favourite-column">
                        <?= zen_draw_form('ami_favourite_' . $amiEscape($key), FILENAME_ADMIN_MENU_ICONS, '', 'post', 'class="ami-favourite-form"') ?>
                            <input type="hidden" name="ami_action" value="favourite"><input type="hidden" name="ami_page" value="<?= $amiEscape($key) ?>">
                            <input type="hidden" name="ami_favourite" value="<?= $favourite ? '0' : '1' ?>">
                            <button type="submit" class="ami-switch" role="switch" aria-checked="<?= $favourite ? 'true' : 'false' ?>" aria-label="<?= $amiEscape(sprintf(AMI_FAVOURITE_LABEL, $name)) ?>"><span></span></button>
                        </form>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
            <?php } ?>
        </table>
    </div>
    <p id="ami-empty" class="ami-empty" hidden><?= AMI_EMPTY ?></p>
    <?php if ($amiSuperuser) { ?>
    <details class="ami-uploads"><summary><?= AMI_GALLERY ?> <span class="badge"><?= count($amiImages) ?></span></summary>
        <div class="ami-upload-gallery">
        <?php foreach ($amiImages as $id => $image) {
            $inUse = false;
            foreach ($amiAssignments as $assignment) {
                if ($assignment['kind'] === 'image' && $assignment['value'] === (string)$id) { $inUse = true; break; }
            }
        ?>
            <div class="ami-upload-card"><img src="<?= $amiEscape($image['src']) ?>" alt="" width="32" height="32"><span><?= $amiEscape($image['label']) ?></span>
            <?= zen_draw_form('ami_delete_' . (int)$id, FILENAME_ADMIN_MENU_ICONS) ?>
                <input type="hidden" name="ami_action" value="delete_image"><input type="hidden" name="ami_value" value="<?= (int)$id ?>">
                <button type="submit" class="btn btn-default btn-sm" <?= $inUse ? 'disabled title="' . $amiEscape(AMI_IN_USE) . '"' : '' ?>><?= AMI_DELETE ?></button>
            </form></div>
        <?php } ?>
        <?php if ($amiImages === []) { ?><p class="text-muted"><?= AMI_NO_UPLOADS ?></p><?php } ?>
        </div>
    </details>
    <dialog id="ami-picker" class="ami-picker" aria-labelledby="ami-picker-title">
        <div class="ami-picker-heading"><div><h2 id="ami-picker-title"><?= AMI_CHOOSE ?></h2><p id="ami-picker-entry"></p></div><button type="button" class="ami-close" aria-label="<?= $amiEscape(AMI_CLOSE) ?>">×</button></div>
        <?= zen_draw_form('ami_pick_icon', FILENAME_ADMIN_MENU_ICONS, '', 'post', 'id="ami-pick-form"') ?>
            <input type="hidden" name="ami_action" value="save_icon"><input type="hidden" name="ami_page" value=""><input type="hidden" name="ami_kind" value="none"><input type="hidden" name="ami_value" value="">
            <div class="ami-picker-actions"><button type="button" class="btn btn-default ami-pick" data-kind="none" data-value=""><?= AMI_NONE ?></button><button type="button" class="btn btn-default ami-pick" data-kind="default" data-value=""><?= AMI_DEFAULT ?></button></div>
            <h3><?= AMI_BUILT_IN ?></h3>
            <label for="ami-icon-search" class="sr-only"><?= AMI_ICON_SEARCH ?></label><input type="search" class="form-control" id="ami-icon-search" placeholder="<?= $amiEscape(AMI_ICON_SEARCH) ?>">
            <div class="ami-icon-grid">
            <?php foreach (AdminMenuIconsCatalog::icons() as $iconValue) { ?><button type="button" class="ami-pick ami-icon-option" data-kind="fa" data-value="<?= $amiEscape($iconValue) ?>" title="<?= $amiEscape($iconValue) ?>"><i class="fa-solid fa-<?= $amiEscape($iconValue) ?>" aria-hidden="true"></i><span><?= $amiEscape(str_replace('-', ' ', $iconValue)) ?></span></button><?php } ?>
            </div>
            <?php if ($amiImages !== []) { ?><h3><?= AMI_UPLOADED ?></h3><div class="ami-icon-grid ami-upload-options">
            <?php foreach ($amiImages as $id => $image) { ?><button type="button" class="ami-pick ami-icon-option" data-kind="image" data-value="<?= (int)$id ?>"><img src="<?= $amiEscape($image['src']) ?>" width="24" height="24" alt=""><span><?= $amiEscape($image['label']) ?></span></button><?php } ?>
            </div><?php } ?>
        </form>
        <section class="ami-upload-section"><h3><?= AMI_UPLOAD ?></h3><p class="text-muted"><?= $amiEscape($amiUploadHelp) ?></p>
        <?= zen_draw_form('ami_upload_icon', FILENAME_ADMIN_MENU_ICONS, '', 'post', 'enctype="multipart/form-data" id="ami-upload-form" data-upload-limit="' . $amiUploadLimit . '" data-size-error="' . $amiEscape(AMI_ERROR_IMAGE_SIZE) . '"') ?>
            <input type="hidden" name="ami_action" value="upload"><input type="hidden" name="ami_page" value="">
            <input type="hidden" name="MAX_FILE_SIZE" value="<?= $amiUploadLimit ?>">
            <label for="ami-upload-label"><?= AMI_LABEL ?></label><input id="ami-upload-label" class="form-control" type="text" name="ami_label" maxlength="100" required>
            <input name="ami_file" type="file" accept="<?= $amiEscape(implode(',', array_column($amiUploadTypes, 'mime'))) ?>" required <?= $amiUploadTypes === [] ? 'disabled' : '' ?> aria-label="<?= $amiEscape(AMI_UPLOAD) ?>">
            <button type="submit" class="btn btn-primary" <?= $amiUploadTypes === [] ? 'disabled' : '' ?>><?= AMI_UPLOAD_APPLY ?></button>
        </form></section>
    </dialog>
    <?php } ?>
    <footer class="ami-footer">Admin Menu Icons &amp; Favourites · v1.1.0</footer>
</main>
<?php require DIR_WS_INCLUDES . 'footer.php'; ?>
</body></html>
