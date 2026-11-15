# Testing

These files are local development fixtures. **Do not upload `tests` to a shop.**
The install ZIP excludes this entire directory.

## Automated database integration checks

`integration.php` uses the official Zen Cart `queryFactory`, query cache, admin-menu functions,
URL helpers and scripted-installer base classes against an isolated MariaDB database.
It checks installation, profile grants, sparse defaults, replacement/removal, stable identifiers
with alphabetical sorting, parameter-specific entries, independent favourites, POST/CSRF/role checks,
invalid input, upload conversion, core cache behaviour, master-switch behaviour, upgrade preservation and complete uninstall.

Prerequisites: PHP with mysqli, GD and the normal Zen Cart extensions; a clean Zen Cart checkout;
a disposable local MariaDB server; and a database administrator named `root` with an empty password on that disposable server.
The harness refuses to run unless `AMI_TEST_DATABASE` is exactly `ami_menu_icons_test`.
It recreates only its own prefixed fixture tables in that database. Never point it at a live shop.

```sh
export ZENCART_ROOT=/absolute/path/to/clean/zencart
export AMI_TEST_DATABASE=ami_menu_icons_test
export AMI_TEST_HOST=127.0.0.1
export AMI_TEST_PORT=33316
php tests/integration.php
```

Repeat against each supported Zen Cart version, using a separate PHP process.
An optional `AMI_TEST_SOCKET` can select the disposable server's socket.

## Browser fixture

After the integration checks, the fixture is left freshly installed.
Run the local server with the same environment:

```sh
php -S 127.0.0.1:8765 tests/router.php
```

Then run `node tests/browser.cjs` with Playwright installed and Chromium available.
`PLAYWRIGHT_MODULE` and `CHROMIUM_PATH` can select existing installations.
The fixture deliberately supplies test logins and a fixed test token; it is not a real admin login.
It uses the core dropdown renderer, sanitizer and styles, with a small simulated application wrapper.

The browser checks cover the master switch and staff restrictions, icon selection/removal, mixed-entry alignment, separate configuration groups,
actual multipart upload-and-assignment, escaping, staff restrictions, independent favourites,
CSRF rejection, responsive layout, and a real 1254px transparent bee upload. Screenshots, if generated, go to the ignored `.artifacts` folder.

## Plugin Manager metadata regression

`plugin_manager.php` runs the official Zen Cart 2.2.1 filesystem scanner and metadata processing,
with repository writes captured in memory. No database or shop is needed.
It reproduces the undefined `pluginId` warning from a malformed manifest, checks that `0` is accepted,
and scans every released AdminMenuIcons version. An optional manifest path checks another custom plugin.

```sh
ZENCART_ROOT=/absolute/path/to/zencart-2.2.1 php tests/plugin_manager.php
```

## Checks performed for v1.1.3

- PHP syntax checks on every plugin and test PHP file.
- JavaScript syntax checks on both plugin scripts.
- 72 database integration checks each with official Zen Cart **2.1.0** and **2.2.1** source, including availability of every picker icon, new-icon selection, conversion from all six supported raster types, processing limits, transparency, migration from the original three-table database and upgrade from v1.1.0.
- Verified all 1,389 names against both actual solid-font character maps, including unique glyphs and preservation of the original 99 names.
- Seven real Plugin Manager scanner checks, including the three-paragraph description and complete release history, plus an optional custom-manifest check.
- PHP **8.4.27**, MariaDB **11.8.9**.
- 75 browser checks with Chromium Headless Shell **156.0.8078.79**, using the 2.2.1 core renderer and sanitizer, including explicit picker context, expanded search and mobile picker behaviour.
- Filter restoration after icon saves, uploads, master-switch saves and reloads; per-login separation within one tab; persistent clearing; normal filtering and picker operation when browser storage is blocked.
- Actual Font Awesome font loading, missing CSS and failed font-file requests; hidden broken glyphs, clear notices, working favourites/uploads/reusable images, and automatic recovery on a later page load with the font restored.
- Desktop and mobile fixture screenshots visually reviewed, including a dropdown mixing an icon and a blank slot.

This is an isolated integration harness, **not a complete installed-store end-to-end test**.

## Store smoke test before community release

1. Install through Modules → Plugin Manager on a staging shop; check logs.
2. Verify global script/style auto-loading, default icons and unchanged top-level headings.
3. Try configuration entries with different `gID` parameters and a third-party plugin entry.
4. Set different favourites in two real admin accounts; verify normal profile restrictions still apply.
5. Assign/remove a built-in icon and upload a transparent PNG. Confirm mixed rows align.
6. Disable/re-enable; verify choices survive and default menu appearance returns while disabled.
7. Upgrade by installing a complete later version folder; verify uploads and preferences remain.
8. On a disposable copy, uninstall and confirm only plugin tables, its page and grants are removed.
9. Check the actual shop's PHP version, admin theme and any custom Content Security Policy.
10. Switch the master control off and back on in a superadmin account, then reload a staff account. Verify choices remain and staff cannot change the master switch.
