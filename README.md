# Admin Menu Icons & Favourites for Zen Cart

Optional icons beside admin dropdown entries, with personal favourites shown in bold.
Top-level headings and menu order stay unchanged. An empty icon space keeps every label aligned.

**Current test build: v1.1.2.** Intended for Zen Cart 2.1.0 and 2.2.1.
See [testing notes](tests/README.md) for the checks performed and the remaining store checks.

## Features

- A small set of starting icons for common entries, including Orders, Customers and Categories / Products.
- Additional starting icons for My Store (house), Images, Customer Details, Email Options, Shipping/Packaging, Stock and Website Maintenance.
- A searchable collection of 1,389 Font Awesome 6 Free solid icons, using the library already included in Zen Cart's admin.
- Use a different built-in icon, upload your own, choose no icon, or restore the starting choice.
- Reuse an uploaded icon across several entries.
- Shared icon assignments, editable only by superadmins.
- Personal favourites, stored against each admin login. Favourites make the existing entry bold.
- A master on/off switch for everyone, operated by superadmins. Turning it off keeps saved choices and leaves this settings page available.
- Discovers the current user's permitted menu entries, including registered third-party plugin pages.
- Handles alphabetically sorted menus, translated labels, renamed admin directories, subdirectory shops and database prefixes.
- Fully encapsulated: no core edits, storefront files, extra icon library, external requests or writable upload directories.

## Requirements

- Zen Cart 2.1.0 or 2.2.1, running a PHP version supported by that release.
- A modern browser with JavaScript and native HTML dialog support.
- The PHP GD extension **only for uploaded images**. Built-in icons and favourites work without GD.
- The usual Zen Cart admin styles and Font Awesome 6 Free solid font.

## Install

1. Download the install ZIP from `packages`, or download and unzip this repository.
2. Upload the **`zc_plugins/AdminMenuIcons`** folder into your shop's existing `zc_plugins` folder.
   The result must be `YOUR_SHOP/zc_plugins/AdminMenuIcons/v1.1.2/manifest.php`.
   Keep the plugin's internal folder named `admin`; do not rename it to match your admin directory.
3. Sign in as a superadmin and open **Modules → Plugin Manager**.
4. Install and enable **Admin Menu Icons & Favourites**.
5. Open **Tools → Admin Menu Icons & Favourites**.

The installer creates its own tables and admin page. There is no separate SQL patch to run.
Do not upload the repository's `tests`, `tools` or `packages` directories into your shop.
Nothing is added to the storefront.

## Use

Use the search and menu filters to find an entry. A superadmin can click **Change** to open the icon picker.
The picker identifies the target as **Changing icon for: Menu → Entry**. Search built-in icons by name; hyphenated names also work.
Your entry search, selected menu and **My favourites only** filter are remembered after saving or reloading in the same browser tab, separately for each admin login. This uses browser session storage; closing the tab clears those filters.

- **No icon** reserves the alignment space but shows no picture.
- **Use starting icon** restores the plugin's default for that entry. Many entries deliberately have no starting icon.
- Selecting a built-in or uploaded icon saves it for everyone.
- Uploading a new image assigns it to the selected entry immediately. It then appears in the reusable collection.

Use the switch in **My favourite** to make that entry bold for your own login. It saves immediately.
It does not move the entry or give access to any additional admin page.

The **Master switch** at the top controls menu icons and bold favourites for every admin.
Only superadmins can change it. When off, the original menu is displayed, and everyone can still edit their permitted preferences ready for when it is turned on again.

Existing admin profiles receive access to this preferences page at installation. This grants no icon-editing rights to ordinary staff.
For profiles created later, grant **Admin Menu Icons & Favourites** under Admins → Admin Profiles if staff need to manage their favourites.
Revoking page access hides the preferences page; previously saved favourites and shared icons can still display in the user's permitted menu.

## Uploads

Accepted input: **PNG, JPEG, GIF, WebP, BMP and AVIF**, where the server's PHP GD library supports each format.
The picker lists the formats available on that server, with a maximum input size of **5 MiB** and **4096 × 4096 pixels**.
If PHP's upload/post limits are lower, the picker shows and enforces the lower size limit.
Images also need to fit within the server's available processing memory; oversized decoded images are rejected before processing.
Images are decoded and re-encoded as PNG, at most 64 pixels on the longest side, retaining their proportions and transparency.
The menu displays them inside a 16 × 16 pixel box. Animated inputs use the first frame.
SVG, ICO, TIFF and HEIC are not accepted in this version.

The small cleaned images are stored in the database, not as publicly accessible uploaded files.
They survive upgrades along with assignments and favourites. The collection is limited to 100 uploads.
Open **Manage uploaded icons** to delete unused images. Assigned images must be unassigned before deletion.

If your admin uses a custom Content Security Policy, its `img-src` must permit `data:` for uploaded icons.
The plugin uses no inline event handlers and adds no external scripts or stylesheets.

### Sample MailBeez upload

[Download the transparent bee PNG](examples/mailbeez-bee.png) to try the upload workflow.
It is a 1254 × 1254 pixel image; upload it as-is and the plugin will shrink and convert it automatically.
The sample is optional, is not assigned automatically, and is not included in the install ZIP.

## Upgrade

Upload the complete new version folder alongside the existing version and use Plugin Manager's upgrade action.
**Do not uninstall first**: uninstall removes saved data. The upgrade code preserves existing data and respects preferences-page access changes.

Upgrading from v1.0.0 creates the master-switch setting automatically. Existing icon assignments, explicit “no icon” choices, uploaded images and personal favourites are retained.
The v1.1.1 update changes the settings layout and expands the icon picker; it keeps the same starting assignments and database structure as v1.1.0.

If Plugin Manager logs an undefined `pluginId`, inspect the manifests of **all** encapsulated plugins.
Opening that page scans every plugin, regardless of which entry is selected. Each manifest needs a `pluginId`; use `0` for an unpublished plugin.
All AdminMenuIcons releases include this field. There is no need to edit the core Plugin Manager.

## Disable or uninstall

- **Disable:** use Plugin Manager, then reload the admin page. Normal menu appearance returns. Saved choices and uploads remain.
- **Uninstall:** Plugin Manager removes the plugin's admin page, its profile grants, and all four plugin tables.
  **This permanently deletes assignments, favourites and uploaded icons.** Keep a database backup if you may want them again.
- After uninstalling, delete `zc_plugins/AdminMenuIcons` from the server if you want to remove the files too.

Other admin permissions, core tables and storefront data are not modified by uninstall.

## Developer notes

The global admin JavaScript loader supplies only the logged-in user's permitted menu entries.
Entries are identified by their registered page keys; labels are used only to recover keys discarded by older core alphabetical sorting.
JavaScript matches the rendered dropdown links, including page parameters such as configuration `gID`, without replacing the menu.

Writes use POST, the admin session's CSRF token, server-side role checks, a permitted-entry whitelist and SQL binding.
The caller's admin identity comes from the session, never a form field.
Uploaded content is decoded and re-encoded before use; original files are not stored or served.

Tables (with the shop's `DB_PREFIX`):

| Table | Contents |
| --- | --- |
| `admin_menu_icon_assignments` | Shared overrides keyed by registered page identifier |
| `admin_menu_icon_favourites` | Personal favourites keyed by admin ID and page identifier |
| `admin_menu_icon_images` | Cleaned PNG uploads and their names |
| `admin_menu_icon_settings` | Store-wide master-switch setting |

All UI strings are in `admin/includes/languages/english/`. Add equivalent language files for translations.
The icon collection and sparse defaults are in `AdminMenuIconsCatalog.php`.

Build an install ZIP with `python3 tools/build_zip.py`. The ZIP excludes tests and development tools.
The `pluginId` is `0` until Zen Cart assigns an ID during community submission; do not invent one.

## Licence and credits

Copyright 2026 Tony Cradwick (**highburyeye**). Licensed under **GNU GPL v2.0 only**; see [LICENSE](LICENSE).
Implementation assisted by Codex. Zen Cart and its included Font Awesome assets retain their respective authorship and licences.
This package does not redistribute Font Awesome.

Repository: https://github.com/highburyeye/zencart-admin-menu-icons
