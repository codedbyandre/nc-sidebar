// SPDX-FileCopyrightText: 2026 André Wiesehoff
// SPDX-License-Identifier: AGPL-3.0-or-later

import type { NavigationApp, Placement } from './types.ts'

/**
 * The lists a settings form keeps. Only personal settings have `pinnedApps`,
 * which shows an app the administrator moved under "More".
 */
export interface AppLists {
	hiddenApps: string[]
	collapsedApps: string[]
	pinnedApps?: string[]
}

/**
 * Where one app sits. Mirrors RailConfig::resolve() on the server: hidden wins
 * over "More", a person's own choice wins over the administrator's "More".
 *
 * @param id - app id
 * @param lists - the form's own lists
 * @param inherited - the administrator's lists, for personal settings
 */
export function placementOf(id: string, lists: AppLists, inherited?: AppLists): Placement {
	if (inherited?.hiddenApps.includes(id) || lists.hiddenApps.includes(id)) {
		return 'hidden'
	}
	if (lists.collapsedApps.includes(id)) {
		return 'more'
	}
	if (lists.pinnedApps?.includes(id)) {
		return 'visible'
	}
	return inherited?.collapsedApps.includes(id) ? 'more' : 'visible'
}

/**
 * Placement of every app, keyed by id.
 *
 * @param apps - apps the form offers
 * @param lists - the form's own lists
 * @param inherited - the administrator's lists, for personal settings
 */
export function placements(apps: NavigationApp[], lists: AppLists, inherited?: AppLists): Record<string, Placement> {
	return Object.fromEntries(apps.map((app) => [app.id, placementOf(app.id, lists, inherited)]))
}

/**
 * Move one app. A choice that equals the inherited placement is not stored,
 * so the app keeps following the administrator.
 *
 * @param lists - the form's own lists, changed in place
 * @param id - app id
 * @param placement - new placement
 * @param inherited - the administrator's lists, for personal settings
 */
export function place(lists: AppLists, id: string, placement: Placement, inherited?: AppLists): void {
	const without = (ids: string[]): string[] => ids.filter((candidate) => candidate !== id)
	lists.hiddenApps = without(lists.hiddenApps)
	lists.collapsedApps = without(lists.collapsedApps)
	if (lists.pinnedApps !== undefined) {
		lists.pinnedApps = without(lists.pinnedApps)
	}
	const fallback: Placement = inherited?.collapsedApps.includes(id) ? 'more' : 'visible'
	if (placement === fallback) {
		return
	}
	if (placement === 'hidden') {
		lists.hiddenApps.push(id)
	} else if (placement === 'more') {
		lists.collapsedApps.push(id)
	} else {
		lists.pinnedApps?.push(id)
	}
}
