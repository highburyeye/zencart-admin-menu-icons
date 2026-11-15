<?php
/** Real Zen Cart 2.2.1 manifest scanner, with in-memory repository writes. GPL-2.0-only. */
error_reporting(E_ALL);
set_error_handler(static function ($level, $message, $file, $line) {
    throw new ErrorException($message, 0, $level, $file, $line);
});
$reference = realpath(getenv('ZENCART_ROOT') ?: '');
if (!$reference || !is_file($reference . '/includes/classes/DbRepositories/PluginControlRepository.php')) {
    throw new RuntimeException('Set ZENCART_ROOT to an official Zen Cart 2.2.1 checkout.');
}
define('DIR_FS_CATALOG', dirname(__DIR__) . '/');
$_SESSION = ['languages_code' => 'en'];
foreach (['DbRepositories/PluginControlRepository', 'DbRepositories/PluginControlVersionRepository', 'PluginSupport/PluginStatus', 'PluginManager'] as $class) {
    require $reference . '/includes/classes/' . $class . '.php';
}
class AmiPluginControlCapture extends Zencart\DbRepositories\PluginControlRepository
{
    public array $rows = [];
    public function __construct() {}
    public function setAllInfs(int $infs): void {}
    public function upsertMany(array $rows): void { $this->rows = $rows; }
    public function deleteByInfs(int $infs): void {}
}
class AmiPluginVersionCapture extends Zencart\DbRepositories\PluginControlVersionRepository
{
    public array $rows = [];
    public function __construct() {}
    public function setAllInfs(int $infs): void {}
    public function upsertMany(array $rows): void { $this->rows = $rows; }
    public function deleteByInfs(int $infs): void {}
}
class AmiManifestScanner extends Zencart\PluginManager\PluginManager
{
    public function inspectManifest(array $manifest): void
    {
        $this->updatePluginControl($this->mergeInVersionInfo([], 'FixturePlugin', ['1.0' => $manifest]));
    }
}
$checks = 0;
function check(bool $ok, string $label): void
{
    if (!$ok) throw new RuntimeException('FAIL: ' . $label);
    $GLOBALS['checks']++;
}
$control = new AmiPluginControlCapture;
$versions = new AmiPluginVersionCapture;
$scanner = new AmiManifestScanner($control, $versions);
$manifest = ['pluginName' => 'Fixture', 'pluginDescription' => '', 'pluginAuthor' => '', 'zcVersions' => []];
try {
    $scanner->inspectManifest($manifest);
    throw new RuntimeException('Missing pluginId should reproduce the core warning.');
} catch (ErrorException $error) {
    check(str_contains($error->getMessage(), 'Undefined array key "pluginId"'), 'missing metadata reproduces reported warning');
}
$scanner->inspectManifest($manifest + ['pluginId' => 0]);
check($control->rows[0]['zc_contrib_id'] === 0, 'zero is accepted for an unpublished plugin');
$scanner->inspectAndUpdate();
check(count($control->rows) === 1 && $control->rows[0]['unique_key'] === 'AdminMenuIcons', 'real filesystem scan finds the plugin');
check(count($versions->rows) === count(glob(dirname(__DIR__) . '/zc_plugins/AdminMenuIcons/*/manifest.php')), 'all released version manifests scan without warnings');
check($control->rows[0]['zc_contrib_id'] === 0, 'latest manifest includes pluginId');
check(substr_count($control->rows[0]['description'], '<p>') === 3 && str_contains($control->rows[0]['description'], 'each admin user manages their own favourites'), 'latest description contains the requested three paragraphs');
$released = array_column($versions->rows, 'version');
usort($released, 'version_compare');
$latest = end($released);
$history = file_get_contents(dirname(__DIR__) . '/zc_plugins/AdminMenuIcons/' . $latest . '/changelog.txt');
check(array_filter($released, static fn($version) => !str_contains($history, $version . ' —')) === [], 'Plugin Manager changelog includes every released version');
if (isset($argv[1])) {
    $scanner->inspectManifest(require $argv[1]);
    check($control->rows[0]['zc_contrib_id'] === 0, 'supplied custom manifest scans without warnings');
}
echo "PASS: $checks real Plugin Manager manifest checks\n";
