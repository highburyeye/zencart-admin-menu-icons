<?php
/** @copyright 2026 Tony Cradwick (highburyeye) @license GPL-2.0-only */
if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true) {
    exit;
}
// Keep the label as plain text. It is SQL-bound on write and HTML-escaped on output.
AdminRequestSanitizer::getInstance()->addComplexSanitization([
    'ami_label' => ['sanitizerType' => 'NULL_ACTION', 'method' => 'post', 'pages' => ['admin_menu_icons']],
]);
