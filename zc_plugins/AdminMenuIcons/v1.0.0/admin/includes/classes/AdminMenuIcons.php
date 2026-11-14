<?php
/** @copyright 2026 Tony Cradwick @license GPL-2.0-only */
if (!defined('IS_ADMIN_FLAG') || IS_ADMIN_FLAG !== true) {
    exit;
}
require_once __DIR__ . '/AdminMenuIconsCatalog.php';

final class AdminMenuIcons
{
    public const MAX_UPLOAD_BYTES = 1048576;
    public const MAX_DIMENSION = 1024;
    public const MAX_IMAGES = 100;

    public function __construct(private $db)
    {
    }

    public static function tables(): array
    {
        return [
            'assignments' => DB_PREFIX . 'admin_menu_icon_assignments',
            'favourites' => DB_PREFIX . 'admin_menu_icon_favourites',
            'images' => DB_PREFIX . 'admin_menu_icon_images',
        ];
    }

    public function menuForUser(): array
    {
        $menu = zen_get_admin_menu_for_user();
        $needsKeys = false;
        foreach ($menu as $pages) {
            foreach (array_keys($pages) as $key) {
                if (is_int($key)) $needsKeys = true;
            }
        }
        if (!$needsKeys) return $menu;
        // Some core versions use usort() for alphabetical menus, discarding page keys.
        $lookup = [];
        foreach ($this->sql('SELECT page_key, menu_key, main_page, page_params, language_key FROM ' . TABLE_ADMIN_PAGES . " WHERE display_on_menu = 'Y' ORDER BY sort_order") as $row) {
            if (!defined($row['main_page']) || !defined($row['language_key'])) continue;
            $signature = constant($row['main_page']) . "\0" . $row['page_params'] . "\0" . constant($row['language_key']);
            $lookup[$row['menu_key']][$signature][] = $row['page_key'];
        }
        foreach ($menu as $group => $pages) {
            $menu[$group] = [];
            foreach ($pages as $key => $page) {
                if (is_int($key)) {
                    $signature = $page['file'] . "\0" . $page['params'] . "\0" . $page['name'];
                    if (empty($lookup[$group][$signature])) continue;
                    $key = array_shift($lookup[$group][$signature]);
                }
                $menu[$group][$key] = $page;
            }
        }
        return $menu;
    }

    private function sql(string $sql, array $values = [])
    {
        $bindings = [];
        foreach ($values as $key => $value) {
            $bindings[':' . $key . ':'] = $this->db->bindVars(':ami_value:', ':ami_value:', $value, is_int($value) ? 'integer' : 'stringIgnoreNull');
        }
        // Non-recursive substitution also preserves labels which contain placeholder-like text.
        $sql = strtr($sql, $bindings);
        // Core memoizes identical queries within a request. Upload-and-assign needs fresh reads.
        return $this->db->Execute($sql, null, false, 0, true);
    }

    public function assignments(): array
    {
        $result = [];
        foreach ($this->sql('SELECT page_key, icon_kind, icon_value FROM ' . self::tables()['assignments']) as $row) {
            $result[$row['page_key']] = ['kind' => $row['icon_kind'], 'value' => $row['icon_value']];
        }
        return $result;
    }

    public function favourites(int $adminId): array
    {
        $result = [];
        foreach ($this->sql('SELECT page_key FROM ' . self::tables()['favourites'] . ' WHERE admin_id = :id:', ['id' => $adminId]) as $row) {
            $result[$row['page_key']] = true;
        }
        return $result;
    }

    public function images(bool $inUseOnly = false): array
    {
        $result = [];
        $where = $inUseOnly ? ' WHERE image_id IN (SELECT icon_value FROM ' . self::tables()['assignments'] . " WHERE icon_kind = 'image')" : '';
        foreach ($this->sql('SELECT image_id, image_label, png_base64 FROM ' . self::tables()['images'] . $where . ' ORDER BY image_id DESC') as $row) {
            $result[(string)$row['image_id']] = [
                'label' => $row['image_label'], 'src' => 'data:image/png;base64,' . $row['png_base64'],
            ];
        }
        return $result;
    }

    public static function effectiveIcon(string $key, array $assignments): array
    {
        if (isset($assignments[$key])) {
            return $assignments[$key];
        }
        $defaults = AdminMenuIconsCatalog::defaults();
        return isset($defaults[$key]) ? ['kind' => 'fa', 'value' => $defaults[$key]] : ['kind' => 'none', 'value' => ''];
    }

    public function payload(array $menu, int $adminId): array
    {
        $assignments = $this->assignments();
        $favourites = $this->favourites($adminId);
        $images = $this->images(true);
        $result = ['entries' => [], 'images' => []];
        foreach ($menu as $pages) {
            foreach ($pages as $key => $page) {
                $icon = self::effectiveIcon((string)$key, $assignments);
                $result['entries'][] = [
                    'key' => (string)$key,
                    'href' => html_entity_decode(zen_href_link($page['file'], $page['params'], 'SSL', false), ENT_QUOTES, 'UTF-8'),
                    'icon' => $icon, 'favourite' => isset($favourites[$key]),
                ];
                if ($icon['kind'] === 'image' && isset($images[$icon['value']])) {
                    $result['images'][$icon['value']] = $images[$icon['value']]['src'];
                }
            }
        }
        $result['ignoreParams'] = [session_name(), 'language'];
        return $result;
    }

    /** All writes pass through this boundary: session identity, CSRF, role and visible page keys. */
    public function dispatch(array $post, array $files, int $adminId, bool $superuser, array $menu, string $sessionToken): array
    {
        if ($adminId < 1) {
            throw new DomainException('forbidden');
        }
        if ($sessionToken === '' || !is_string($post['securityToken'] ?? null)
            || !hash_equals($sessionToken, $post['securityToken'])) {
            throw new DomainException('csrf');
        }
        $action = self::field($post, 'ami_action');
        if (!in_array($action, ['favourite', 'save_icon', 'upload', 'delete_image'], true)) {
            throw new DomainException('action');
        }
        if ($action !== 'favourite' && !$superuser) {
            throw new DomainException('forbidden');
        }
        $key = self::field($post, 'ami_page');
        $keys = [];
        foreach ($menu as $pages) {
            $keys += $pages;
        }
        if (($action === 'favourite' || $action === 'save_icon' || ($action === 'upload' && $key !== ''))
            && !array_key_exists($key, $keys)) {
            throw new DomainException('page');
        }
        if ($action === 'favourite') {
            $value = self::field($post, 'ami_favourite');
            if (!in_array($value, ['0', '1'], true)) {
                throw new DomainException('action');
            }
            $table = self::tables()['favourites'];
            $this->sql($value === '1'
                ? 'REPLACE INTO ' . $table . ' (admin_id, page_key) VALUES (:id:, :key:)'
                : 'DELETE FROM ' . $table . ' WHERE admin_id = :id: AND page_key = :key:',
                ['id' => $adminId, 'key' => $key]);
            return ['message' => 'saved', 'key' => $key, 'favourite' => $value === '1'];
        }
        if ($action === 'save_icon') {
            $this->saveIcon($key, self::field($post, 'ami_kind'), self::field($post, 'ami_value'));
        } elseif ($action === 'upload') {
            $file = $files['ami_file'] ?? [];
            if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
                || !is_string($file['tmp_name'] ?? null) || !is_uploaded_file($file['tmp_name'])) {
                throw new DomainException('upload');
            }
            if (filesize($file['tmp_name']) > self::MAX_UPLOAD_BYTES) {
                throw new DomainException('image_size');
            }
            if (count($this->images()) >= self::MAX_IMAGES) {
                throw new DomainException('image_limit');
            }
            $png = self::normaliseImage((string)file_get_contents($file['tmp_name']));
            $label = trim(self::field($post, 'ami_label'));
            if ($label === '' || !mb_check_encoding($label, 'UTF-8') || mb_strlen($label, 'UTF-8') > 100) {
                throw new DomainException('label');
            }
            $this->sql('INSERT INTO ' . self::tables()['images'] . ' (image_label, png_base64) VALUES (:label:, :data:)',
                ['label' => $label, 'data' => base64_encode($png)]);
            $id = (string)$this->db->Insert_ID();
            if ($key !== '') {
                $this->saveIcon($key, 'image', $id);
            }
        } else {
            $id = self::field($post, 'ami_value');
            if (!ctype_digit($id) || !isset($this->images()[$id])) {
                throw new DomainException('icon');
            }
            foreach ($this->assignments() as $assignment) {
                if ($assignment['kind'] === 'image' && $assignment['value'] === $id) {
                    throw new DomainException('in_use');
                }
            }
            $this->sql('DELETE FROM ' . self::tables()['images'] . ' WHERE image_id = :id:', ['id' => (int)$id]);
        }
        return ['message' => 'saved', 'key' => $key];
    }

    private static function field(array $post, string $key): string
    {
        if (!isset($post[$key])) {
            return '';
        }
        if (!is_string($post[$key])) {
            throw new DomainException('action');
        }
        return $post[$key];
    }

    private function saveIcon(string $key, string $kind, string $value): void
    {
        $table = self::tables()['assignments'];
        if ($kind === 'default') {
            $this->sql('DELETE FROM ' . $table . ' WHERE page_key = :key:', ['key' => $key]);
            return;
        }
        if (!in_array($kind, ['none', 'fa', 'image'], true)
            || ($kind === 'fa' && !in_array($value, AdminMenuIconsCatalog::icons(), true))
            || ($kind === 'image' && (!ctype_digit($value) || !isset($this->images()[$value])))) {
            throw new DomainException('icon');
        }
        $this->sql('REPLACE INTO ' . $table . ' (page_key, icon_kind, icon_value) VALUES (:key:, :kind:, :value:)',
            ['key' => $key, 'kind' => $kind, 'value' => $kind === 'none' ? '' : $value]);
    }

    /** Decode and re-encode raster input; original files and metadata are never served. */
    public static function normaliseImage(string $data): string
    {
        if ($data === '' || strlen($data) > self::MAX_UPLOAD_BYTES) {
            throw new DomainException('image_size');
        }
        if (!function_exists('imagecreatefromstring')) {
            throw new DomainException('gd');
        }
        $info = @getimagesizefromstring($data);
        if ($info === false || !in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true)) {
            throw new DomainException('image');
        }
        if ($info[0] < 1 || $info[1] < 1 || $info[0] > self::MAX_DIMENSION || $info[1] > self::MAX_DIMENSION) {
            throw new DomainException('image_size');
        }
        $source = @imagecreatefromstring($data);
        if ($source === false) {
            throw new DomainException('image');
        }
        $scale = min(1, 64 / max($info[0], $info[1]));
        $width = max(1, (int)round($info[0] * $scale));
        $height = max(1, (int)round($info[1] * $scale));
        $target = imagecreatetruecolor($width, $height);
        imagealphablending($target, false);
        imagesavealpha($target, true);
        imagefill($target, 0, 0, imagecolorallocatealpha($target, 0, 0, 0, 127));
        imagecopyresampled($target, $source, 0, 0, 0, 0, $width, $height, $info[0], $info[1]);
        ob_start();
        imagepng($target, null, 9);
        $png = (string)ob_get_clean();
        return $png;
    }
}
