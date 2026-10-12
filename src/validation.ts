// SPDX-FileCopyrightText: 2026 André Wiesehoff
// SPDX-License-Identifier: AGPL-3.0-or-later

import type { ColorMode, FieldError } from './types.ts'

import { t } from '@nextcloud/l10n'
import { normalizeHex } from './theme.ts'

/**
 * Checks the server repeats; this copy only answers before the round trip.
 *
 * @param mode - selected color source, null for the administrator's default
 * @param customColor - typed hex value
 */
export function colorProblems(mode: ColorMode | null, customColor: string | null): FieldError[] {
	if (mode !== 'custom') {
		return []
	}
	if (customColor === null || customColor.trim() === '') {
		return [{ field: 'customColor', code: 'required', message: '' }]
	}
	if (normalizeHex(customColor) === null) {
		return [{ field: 'customColor', code: 'color', message: '' }]
	}
	return []
}

/**
 * Localized text for a field error from either side.
 *
 * @param error - field error
 */
export function errorText(error: FieldError): string {
	switch (error.code) {
		case 'required':
			return t('sidebar', 'Enter a color for the custom color option.')
		case 'color':
			return t('sidebar', 'Enter a hex color such as #0082c9.')
		case 'locked':
			return t('sidebar', 'Your administrator manages this setting.')
		case 'app_ids':
			return t('sidebar', 'The list of hidden apps is not valid.')
		default:
			return t('sidebar', 'The value of {field} is not valid.', { field: error.field })
	}
}
