<?php
/** @copyright 2026 Tony Cradwick @license GPL-2.0-only */
if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true) {
    exit;
}
$autoLoadConfig[69][] = ['autoType' => 'init_script', 'loadFile' => 'init_admin_menu_icons_sanitizers.php'];
