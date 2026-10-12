// SPDX-FileCopyrightText: 2026 André Wiesehoff
// SPDX-License-Identifier: AGPL-3.0-or-later

import type { Ref } from 'vue'

import { onBeforeUnmount, ref } from 'vue'

/**
 * Reactive match state of a media query.
 *
 * @param query - CSS media query
 */
export function useMediaQuery(query: string): Ref<boolean> {
	const list = window.matchMedia(query)
	const matches = ref(list.matches)
	const update = (event: MediaQueryListEvent): void => {
		matches.value = event.matches
	}
	list.addEventListener('change', update)
	onBeforeUnmount(() => list.removeEventListener('change', update))
	return matches
}
