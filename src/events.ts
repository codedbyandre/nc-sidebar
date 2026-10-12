// SPDX-FileCopyrightText: 2026 André Wiesehoff
// SPDX-License-Identifier: AGPL-3.0-or-later

import type { RailConfig } from './types.ts'

import { emit } from '@nextcloud/event-bus'

export const RAIL_CONFIG_EVENT = 'sidebar:rail-config'

/**
 * Tell the rail on this page that its saved configuration changed.
 *
 * @param config - configuration returned by the server
 */
export function announceRailConfig(config: RailConfig): void {
	emit(RAIL_CONFIG_EVENT, config)
}
