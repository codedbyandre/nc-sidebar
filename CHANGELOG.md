<!--
  SPDX-FileCopyrightText: 2026 André Wiesehoff
  SPDX-License-Identifier: AGPL-3.0-or-later
-->

# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 0.4.0 - 2026-11-13

### Added

- Apps can sit under “More”: a button below the visible apps unfolds them in the sidebar, and each person's choice to keep them unfolded is remembered (#2).
- Administrators choose for each app whether it is in the sidebar, under “More”, or hidden. People can rearrange apps for themselves when allowed, including showing an app the administrator moved under “More” (#2).
- A button at the start of the header, and Alt+Shift+H, hide the sidebar on the desktop and bring it back. The choice is remembered for each person (#2).
- Nextcloud's app menu can stay in the header, as an administrator setting that people may override when layout changes are allowed (#2).

### Changed

- Renamed the administrators' link to the app management page from “More apps” to “Manage apps”.

## 0.3.1 - 2026-11-12

### Fixed

- Hid the sidebar while an app shows a fullscreen view without the Nextcloud header, such as the EuroOffice editor, so it no longer covers the editor (#1).
- Kept the content of apps that position their own container, such as Markdown Wiki, beside the sidebar instead of underneath it (#1).

### Security

- Updated frontend dependencies, including DOMPurify 3.4.17, to close published advisories.

## 0.3.0 - 2026-10-13

### Changed

- Widened the compact and labeled rail modes and scaled up the header logo so custom logos and app captions remain legible.
- Rendered footer entries in the narrow rail as compact icon buttons with tooltips and active state on the app management page.

### Fixed

- Hid the scrollbar gutter inside the app list so icons and active selection pills stay horizontally centered.
- Kept the active app entry scrolled into view on load and aligned RTL tooltip positioning with Nextcloud's body direction attribute.
- Hardened link hrefs, icon mask URLs, and theme CSS variables against invalid or unsafe values.

## 0.2.0 - 2026-10-12

### Added

- Narrow rail with tooltips and a wide rail with app names, switched with a button or Alt+Shift+S and remembered per person.
- Drawer with a menu button in the header on screens up to 1024 pixels wide.
- Color modes: theming color, neutral surface or a custom color with automatic text contrast.
- Administration settings for defaults, hidden apps and what people may change.
- Personal settings for color, app names and hidden apps.
- Validation of every setting on the server, with field errors in the settings forms.
- German translation of all texts.
- The open app, or the open settings section, is shown in the header where the app menu used to be.

### Fixed

- App icons were not valid UTF-8 SVG files.
- Apps that mount their own content container, such as the settings pages, ran past the right edge of the window.

## 0.1.0 - 2026-10-12

### Added

- App skeleton that replaces the apps menu with a left column of icons.
