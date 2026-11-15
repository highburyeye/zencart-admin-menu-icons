<?php
/** @copyright 2026 Tony Cradwick (highburyeye) @license GPL-2.0-only */
use Zencart\PluginSupport\ScriptedInstaller as ScriptedInstallBase;

if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true) {
    exit;
}
class ScriptedInstaller extends ScriptedInstallBase
{
    // Keep installers independent of runtime classes from another active version during upgrade.
    private static function tables(): array
    {
        return [
            'assignments' => DB_PREFIX . 'admin_menu_icon_assignments',
            'favourites' => DB_PREFIX . 'admin_menu_icon_favourites',
            'images' => DB_PREFIX . 'admin_menu_icon_images',
            'settings' => DB_PREFIX . 'admin_menu_icon_settings',
        ];
    }

    protected function executeInstall()
    {
        $tables = self::tables();
        $queries = [
            'CREATE TABLE IF NOT EXISTS ' . $tables['assignments'] . ' (
                page_key VARCHAR(64) COLLATE utf8mb4_bin NOT NULL PRIMARY KEY,
                icon_kind VARCHAR(8) NOT NULL, icon_value VARCHAR(100) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ' . $tables['favourites'] . ' (
                admin_id INT NOT NULL, page_key VARCHAR(64) COLLATE utf8mb4_bin NOT NULL,
                PRIMARY KEY (admin_id, page_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ' . $tables['images'] . ' (
                image_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                image_label VARCHAR(100) NOT NULL, png_base64 MEDIUMTEXT NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS ' . $tables['settings'] . ' (
                setting_key VARCHAR(64) NOT NULL PRIMARY KEY, setting_value VARCHAR(255) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'INSERT IGNORE INTO ' . $tables['settings'] . " (setting_key, setting_value) VALUES ('enabled', '1')",
        ];
        foreach ($queries as $sql) {
            if (!$this->executeInstallerSql($sql)) {
                return false;
            }
        }
        // Grant only this personal-preferences page, once. Upgrades respect later permission changes.
        if (!zen_page_key_exists('toolsAdminMenuIcons')) {
            zen_register_admin_page('toolsAdminMenuIcons', 'BOX_TOOLS_ADMIN_MENU_ICONS', 'FILENAME_ADMIN_MENU_ICONS', '', 'tools', 'Y');
            if (!$this->executeInstallerSql('INSERT INTO ' . TABLE_ADMIN_PAGES_TO_PROFILES . " (profile_id, page_key)
                SELECT profile_id, 'toolsAdminMenuIcons' FROM " . TABLE_ADMIN_PROFILES . " p
                WHERE NOT EXISTS (SELECT 1 FROM " . TABLE_ADMIN_PAGES_TO_PROFILES . " x
                    WHERE x.profile_id = p.profile_id AND x.page_key = 'toolsAdminMenuIcons')")) {
                return false;
            }
        }
        return true;
    }

    protected function executeUpgrade($oldVersion)
    {
        return $this->executeInstall();
    }

    protected function executeUninstall()
    {
        // The manager may render its header using the plugin list from the start of this request.
        $GLOBALS['ami_uninstalled'] = true;
        foreach (self::tables() as $table) {
            if (!$this->executeInstallerSql('DROP TABLE IF EXISTS ' . $table)) {
                return false;
            }
        }
        if (!$this->executeInstallerSql('DELETE FROM ' . TABLE_ADMIN_PAGES_TO_PROFILES . " WHERE page_key = 'toolsAdminMenuIcons'")) {
            return false;
        }
        zen_deregister_admin_pages(['toolsAdminMenuIcons']);
        return true;
    }
}
