<!--
  SPDX-FileCopyrightText: 2026 André Wiesehoff
  SPDX-License-Identifier: AGPL-3.0-or-later
-->

# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
