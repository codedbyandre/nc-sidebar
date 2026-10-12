// SPDX-FileCopyrightText: 2026 André Wiesehoff
// SPDX-License-Identifier: AGPL-3.0-or-later

import type { ColorMode, RailConfig } from './types.ts'

const LIGHT_TEXT = '#ffffff'
const DARK_TEXT = '#000000'
const HEX = /^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/i

/**
 * Lowercase #rrggbb, or null when the input is not a hex color.
 * Mirrors HexColor::parse() on the server.
 *
 * @param raw - user input
 */
export function normalizeHex(raw: string): string | null {
	const value = raw.trim().toLowerCase()
	if (!HEX.test(value)) {
		return null
	}
	if (value.length === 4) {
		return `#${value[1]}${value[1]}${value[2]}${value[2]}${value[3]}${value[3]}`
	}
	return value
}

/**
 * WCAG 2.x relative luminance.
 *
 * @param hex - normalized #rrggbb
 */
function luminance(hex: string): number {
	const [r, g, b] = [1, 3, 5].map((offset) => {
		const channel = parseInt(hex.slice(offset, offset + 2), 16) / 255
		return channel <= 0.04045 ? channel / 12.92 : ((channel + 0.055) / 1.055) ** 2.4
	})
	return 0.2126 * r + 0.7152 * g + 0.0722 * b
}

/**
 * WCAG contrast ratio between two colors.
 *
 * @param a - normalized #rrggbb
 * @param b - normalized #rrggbb
 */
export function contrast(a: string, b: string): number {
	const [lighter, darker] = [luminance(a), luminance(b)].sort((x, y) => y - x)
	return (lighter + 0.05) / (darker + 0.05)
}

/**
 * Text color with the higher contrast on the given background.
 *
 * @param background - normalized #rrggbb
 */
export function readableText(background: string): string {
	return contrast(background, LIGHT_TEXT) >= contrast(background, DARK_TEXT) ? LIGHT_TEXT : DARK_TEXT
}

/**
 * CSS variables for a color mode, for previews before saving.
 * Mirrors RailTheme::for() on the server, which stays authoritative.
 *
 * @param mode - color source
 * @param customColor - hex color for the custom mode
 */
export function themeVariables(mode: ColorMode, customColor: string | null): Record<string, string> {
	const custom = customColor === null ? null : normalizeHex(customColor)
	if (mode === 'custom' && custom !== null) {
		return {
			'--nc-sidebar-background': custom,
			'--nc-sidebar-text': readableText(custom),
			'--nc-sidebar-border': 'transparent',
		}
	}
	if (mode === 'neutral') {
		return {
			'--nc-sidebar-background': 'var(--color-main-background)',
			'--nc-sidebar-text': 'var(--color-main-text)',
			'--nc-sidebar-border': 'var(--color-border)',
		}
	}
	return {
		'--nc-sidebar-background': 'var(--color-primary)',
		'--nc-sidebar-text': 'var(--color-primary-text)',
		'--nc-sidebar-border': 'transparent',
	}
}

/**
 * CSS mask that paints a monochrome icon in the current text color.
 *
 * @param url - icon URL
 */
export function iconMask(url: string): Record<string, string> {
	const value = `url("${url.replace(/"/g, '%22')}")`
	return { maskImage: value, WebkitMaskImage: value }
}

/**
 * Width token for the desktop rail. Mirrors RailConfig::widthVariable().
 *
 * @param expanded - wide rail with names
 * @param showLabels - captions under the icons in the narrow rail
 */
export function widthVariable(expanded: boolean, showLabels: boolean): string {
	if (expanded) {
		return 'var(--nc-sidebar-width-expanded)'
	}
	return showLabels ? 'var(--nc-sidebar-width-labeled)' : 'var(--nc-sidebar-width-compact)'
}

/**
 * Write the rail's width and colors onto the body, overriding the inline style
 * the server rendered into the page head. The body carries the theme
 * attributes, so theme variables only resolve correctly from there.
 *
 * @param config - resolved rail configuration
 * @param expanded - current expanded state, which can differ from the stored one
 */
export function applyRailVariables(config: RailConfig, expanded: boolean): void {
	const root = document.body.style
	root.setProperty('--nc-sidebar-width', widthVariable(expanded, config.showLabels))
	for (const [name, value] of Object.entries(config.theme.variables)) {
		root.setProperty(name, value)
	}
}
