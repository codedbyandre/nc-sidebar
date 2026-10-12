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
}

export interface AdminSettings {
	colorMode: ColorMode
	customColor: string | null
	showLabels: boolean
	expandedByDefault: boolean
	showLogo: boolean
	showMoreApps: boolean
	hiddenApps: string[]
	allowUserColor: boolean
	allowUserLayout: boolean
	allowUserHiddenApps: boolean
}

export interface UserPreferences {
	colorMode: ColorMode | null
	customColor: string | null
	showLabels: boolean | null
	hiddenApps: string[]
	expanded: boolean | null
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
