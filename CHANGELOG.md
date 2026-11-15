# Changelog

## v1.1.3 — 15/11/2026

- Check that Font Awesome styles and the actual font file are available before displaying built-in icons.
- Hide unavailable glyphs while keeping icon alignment, uploaded images and personal favourites working.
- Explain missing Font Awesome on the settings page and in the picker; keep uploads and saved choices available.
- Update the Plugin Manager description and display it as three paragraphs.
- Include the complete release history in Plugin Manager's local changelog.

## v1.1.2 — 15/11/2026

- Remember the entry search, selected menu and favourites-only filter after saves and page reloads.
- Keep filter state separate for each admin login and shop within the current browser tab.
- Ignore unavailable menu groups and continue normally if browser storage is disabled or full.

## v1.1.1 — 15/11/2026

- Flattened the settings page: neutral admin colours, subtle 2px corners, plainer tables and a simpler heading.
- Made the picker target explicit with “Changing icon for: Menu → Entry”, including screen-reader context.
- Expanded the searchable Font Awesome collection from 99 to 1,389 distinct solid icons, verified against the fonts in both supported Zen Cart versions.
- Kept all previous icon names valid, the existing sparse defaults and all saved choices.
- Added hyphenated-name search and a clear message when no icons match.
- Added a real Plugin Manager scanner regression check and troubleshooting notes for missing metadata in other plugins.

## v1.1.0 — 15/11/2026

- Added a superadmin-controlled master switch; disabling visuals retains all saved choices.
- Added seven starting icons, including a house for My Store, pictures for Images and an address book for Customer Details.
- Expanded raster uploads to PNG, JPEG, GIF, WebP, BMP and AVIF according to server capabilities.
- Raised input limits to 5 MiB / 4096 pixels, respecting lower PHP limits and available processing memory.
- Kept automatic resizing, PNG conversion and transparency; added clear format and size help in the picker.
- Expanded the Plugin Manager description and author credit to Tony Cradwick (highburyeye).
- Added an optional coloured bee PNG for MailBeez upload testing, outside the install ZIP.
- Verified migration from the original three-table database and preservation of the master-switch state on later upgrades.

## v1.0.0 — 15/11/2026

First build for store testing.

- Optional left-hand dropdown icons with consistent label alignment.
- Sparse starting assignments and a searchable Font Awesome picker.
- Validated image uploads, cleaned PNG storage and a reusable upload collection.
- Shared icons managed by superadmins, with personal favourites for each admin login.
- Stable assignments for alphabetically sorted and parameter-specific menu entries.
- Encapsulated installer, upgrade and complete uninstall support.
