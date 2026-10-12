// SPDX-FileCopyrightText: 2026 André Wiesehoff
// SPDX-License-Identifier: AGPL-3.0-or-later

import type { Ref } from 'vue'
import type { FieldError } from '../types.ts'

import { t } from '@nextcloud/l10n'
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { RequestFailed } from '../api.ts'
import { errorText } from '../validation.ts'

const SAVED_NOTICE_MS = 5000

/**
 * Deep copy of plain JSON data. structuredClone rejects reactive proxies.
 *
 * @param value - JSON-compatible value
 */
function clone<T>(value: T): T {
	return JSON.parse(JSON.stringify(value)) as T
}

/**
 * Draft, saved copy and request state of one settings form.
 *
 * @param initial - saved values from the initial state
 * @param validate - client-side checks, run before every save
 */
export function useSettingsForm<T extends object>(initial: T, validate: (draft: T) => FieldError[]) {
	const saved = ref(clone(initial)) as Ref<T>
	const draft = ref(clone(initial)) as Ref<T>
	const busy = ref(false)
	const savedNotice = ref(false)
	const error = ref<string | null>(null)
	const fieldErrors = ref<FieldError[]>([])
	let noticeTimer: number | undefined

	const dirty = computed(() => JSON.stringify(draft.value) !== JSON.stringify(saved.value))

	/**
	 * @param field - field name as the server reports it
	 */
	function fieldError(field: string): string | null {
		const match = fieldErrors.value.find((candidate) => candidate.field === field)
		return match === undefined ? null : errorText(match)
	}

	/**
	 * Replace both copies with what the server stored.
	 *
	 * @param values - stored values
	 */
	function accept(values: T): void {
		saved.value = clone(values)
		draft.value = clone(values)
	}

	/**
	 * Run one request and translate its outcome into form state.
	 *
	 * @param request - request that resolves to the stored values
	 * @param checkFirst - run client-side validation before sending
	 */
	async function submit(request: () => Promise<T>, checkFirst = true): Promise<boolean> {
		window.clearTimeout(noticeTimer)
		savedNotice.value = false
		error.value = null
		fieldErrors.value = checkFirst ? validate(draft.value) : []
		if (fieldErrors.value.length > 0) {
			error.value = t('sidebar', 'Check the highlighted fields.')
			return false
		}
		busy.value = true
		try {
			accept(await request())
			savedNotice.value = true
			noticeTimer = window.setTimeout(() => {
				savedNotice.value = false
			}, SAVED_NOTICE_MS)
			return true
		} catch (failure: unknown) {
			if (failure instanceof RequestFailed && failure.errors.length > 0) {
				fieldErrors.value = failure.errors
				error.value = failure.errors.map(errorText).join(' ')
			} else {
				error.value = t('sidebar', 'The settings could not be saved. Check your connection and try again.')
			}
			return false
		} finally {
			busy.value = false
		}
	}

	/**
	 * Throw the draft away.
	 */
	function discard(): void {
		draft.value = clone(saved.value)
		fieldErrors.value = []
		error.value = null
	}

	/**
	 * @param event - page unload
	 */
	function guardUnload(event: BeforeUnloadEvent): void {
		if (dirty.value) {
			event.preventDefault()
		}
	}

	watch(draft, () => {
		fieldErrors.value = []
		error.value = null
	}, { deep: true })

	onMounted(() => window.addEventListener('beforeunload', guardUnload))
	onBeforeUnmount(() => {
		window.removeEventListener('beforeunload', guardUnload)
		window.clearTimeout(noticeTimer)
	})

	return { draft, saved, dirty, busy, savedNotice, error, fieldErrors, fieldError, submit, discard }
}
