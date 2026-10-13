// SPDX-FileCopyrightText: 2026 André Wiesehoff
// SPDX-License-Identifier: AGPL-3.0-or-later

import type { Ref } from 'vue'

import { onBeforeUnmount, ref } from 'vue'

export interface Tooltip {
	text: string
	top: number
	inlineOffset: number
}

const HOVER_DELAY = 350
const WARM_WINDOW = 400
const GAP = 10

/**
 * One tooltip for a column of targets. After one tooltip was shown, moving
 * to the next target shows its tooltip without the delay.
 */
export function useTooltip(): {
	tooltip: Ref<Tooltip | null>
	show: (target: HTMLElement, text: string, immediate?: boolean) => void
	hide: () => void
} {
	const tooltip = ref<Tooltip | null>(null)
	let timer: number | undefined
	let warmUntil = 0

	const place = (target: HTMLElement, text: string): void => {
		const rect = target.getBoundingClientRect()
		const rtl = document.documentElement.dir === 'rtl' || document.body?.dir === 'rtl'
		tooltip.value = {
			text,
			top: rect.top + rect.height / 2,
			inlineOffset: rtl ? window.innerWidth - rect.left + GAP : rect.right + GAP,
		}
	}

	const show = (target: HTMLElement, text: string, immediate = false): void => {
		window.clearTimeout(timer)
		if (immediate || Date.now() < warmUntil || tooltip.value !== null) {
			place(target, text)
			return
		}
		timer = window.setTimeout(() => place(target, text), HOVER_DELAY)
	}

	const hide = (): void => {
		window.clearTimeout(timer)
		if (tooltip.value !== null) {
			warmUntil = Date.now() + WARM_WINDOW
		}
		tooltip.value = null
	}

	onBeforeUnmount(() => window.clearTimeout(timer))

	return { tooltip, show, hide }
}
