// SPDX-FileCopyrightText: 2026 André Wiesehoff
// SPDX-License-Identifier: AGPL-3.0-or-later

import type { NavigationEntry, RailConfig } from './types.ts'

declare module '@nextcloud/event-bus' {
	interface NextcloudEvents {
		'nextcloud:app-menu.refresh': { apps: NavigationEntry[] }
		'sidebar:rail-config': RailConfig
	}
}
