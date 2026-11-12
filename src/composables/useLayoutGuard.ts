// SPDX-FileCopyrightText: 2026 André Wiesehoff
// SPDX-License-Identifier: AGPL-3.0-or-later

import type { Ref } from 'vue'

import { onBeforeUnmount, onMounted, ref, watch } from 'vue'

export const FULLSCREEN_CLASS = 'nc-sidebar-fullscreen'
const SHIFT_PROPERTY = 'margin-inline-start'
const SHIFT_VALUE = 'var(--nc-sidebar-width)'

/**
 * Whether an element takes no space on the page.
 *
 * @param element - element to test
 */
export function isHidden(element: Element): boolean {
	const style = getComputedStyle(element)
	return style.display === 'none' || style.visibility === 'hidden' || element.getClientRects().length === 0
}

/**
 * Whether the content starts underneath the rail. One pixel of tolerance
 * absorbs rounding while both are animating.
 *
 * @param rail - box of the rail
 * @param content - box of the page content
 * @param rtl - right-to-left layout, the rail sits on the right
 */
export function overlaps(rail: DOMRectReadOnly, content: DOMRectReadOnly, rtl: boolean): boolean {
	if (rail.width === 0 || content.width === 0) {
		return false
	}
	return rtl ? content.right > rail.left + 1 : content.left < rail.right - 1
}

/**
 * Keeps the rail out of the way of apps that change the page layout themselves.
 *
 * - An app that hides the Nextcloud header for a fullscreen view, such as an
 *   inline office editor, also hides the rail. The rail returns with the header.
 * - An app that positions the content container itself, so the rail's offset
 *   does not apply, gets the offset as an inline style instead.
 *
 * @param rail - the rail's panel
 * @param desktop - the rail is a column beside the content, not a drawer
 * @param rtl - right-to-left layout
 */
export function useLayoutGuard(rail: Ref<HTMLElement | null>, desktop: Ref<boolean>, rtl: boolean): { fullscreen: Ref<boolean> } {
	const fullscreen = ref(false)
	const header = document.getElementById('header')
	let shifted: HTMLElement | null = null
	let resizeObserver: ResizeObserver | null = null
	let mutationObserver: MutationObserver | null = null

	const content = (): HTMLElement | null => document.getElementById('content') ?? document.getElementById('content-vue')

	const unshift = (): void => {
		shifted?.style.removeProperty(SHIFT_PROPERTY)
		shifted = null
	}

	const detectFullscreen = (): void => {
		fullscreen.value = header !== null && isHidden(header)
		document.documentElement.classList.toggle(FULLSCREEN_CLASS, fullscreen.value)
	}

	// Once shifted, the content stays shifted: measuring again would first have
	// to remove the offset, which restarts the content's width transition.
	const fitContent = (): void => {
		const target = content()
		if (fullscreen.value || !desktop.value || target === null || rail.value === null) {
			unshift()
			return
		}
		if (shifted === target) {
			return
		}
		unshift()
		if (overlaps(rail.value.getBoundingClientRect(), target.getBoundingClientRect(), rtl)) {
			target.style.setProperty(SHIFT_PROPERTY, SHIFT_VALUE, 'important')
			shifted = target
		}
	}

	const update = (): void => {
		detectFullscreen()
		fitContent()
	}

	onMounted(() => {
		// Resizes catch a header that disappears. Attribute changes catch apps that
		// move the content by class or style without changing its size.
		resizeObserver = new ResizeObserver(update)
		mutationObserver = new MutationObserver(update)
		for (const element of [header, content()]) {
			if (element !== null) {
				resizeObserver.observe(element)
				mutationObserver.observe(element, { attributes: true, attributeFilter: ['class', 'style'] })
			}
		}
		mutationObserver.observe(document.body, { attributes: true, attributeFilter: ['class'] })
		update()
	})

	watch(desktop, update, { flush: 'post' })

	onBeforeUnmount(() => {
		resizeObserver?.disconnect()
		mutationObserver?.disconnect()
		unshift()
		document.documentElement.classList.remove(FULLSCREEN_CLASS)
	})

	return { fullscreen }
}
