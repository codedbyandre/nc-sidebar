// SPDX-FileCopyrightText: 2026 André Wiesehoff
// SPDX-License-Identifier: AGPL-3.0-or-later

export interface NavigationEntry {
	id: string
	active: boolean
	order: number
	href: string
	icon: string
	type: 'link' | 'settings'
	name: string
	app?: string
	unread: number
	target?: boolean
}

export type ColorMode = 'theme' | 'neutral' | 'custom'

/** Where an app sits: in the sidebar, under "More", or nowhere */
export type Placement = 'visible' | 'more' | 'hidden'

export interface RailTheme {
	mode: ColorMode
	variables: Record<string, string>
}

export interface RailConfig {
	theme: RailTheme
	showLabels: boolean
	expanded: boolean
	showLogo: boolean
	showMoreApps: boolean
	hiddenApps: string[]
	collapsedApps: string[]
	moreOpen: boolean
	railHidden: boolean
	keepAppMenu: boolean
}

export interface AdminSettings {
	colorMode: ColorMode
	customColor: string | null
	showLabels: boolean
	expandedByDefault: boolean
	showLogo: boolean
	showMoreApps: boolean
	hiddenApps: string[]
	collapsedApps: string[]
	keepAppMenu: boolean
	allowUserColor: boolean
	allowUserLayout: boolean
	allowUserHiddenApps: boolean
}

export interface UserPreferences {
	colorMode: ColorMode | null
	customColor: string | null
	showLabels: boolean | null
	keepAppMenu: boolean | null
	hiddenApps: string[]
	collapsedApps: string[]
	pinnedApps: string[]
	expanded: boolean | null
	moreOpen: boolean | null
	railHidden: boolean | null
}

export interface NavigationApp {
	id: string
	name: string
	icon: string
}

export interface FieldError {
	field: string
	code: string
	message: string
}
