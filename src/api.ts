// SPDX-FileCopyrightText: 2026 André Wiesehoff
// SPDX-License-Identifier: AGPL-3.0-or-later

import type { AdminSettings, FieldError, RailConfig, UserPreferences } from './types.ts'

import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'

export interface AdminSettingsResponse {
	settings: AdminSettings
	rail: RailConfig | null
}

export interface PreferencesResponse {
	preferences: UserPreferences
	defaults: AdminSettings
	rail: RailConfig
}

/**
 * Rejected request with the server's field errors, if it sent any.
 */
export class RequestFailed extends Error {
	constructor(
		public readonly status: number,
		public readonly errors: FieldError[],
	) {
		super(`Request failed with status ${status}`)
	}
}

const adminUrl = (): string => generateOcsUrl('/apps/sidebar/api/v1/admin/settings')
const preferencesUrl = (): string => generateOcsUrl('/apps/sidebar/api/v1/preferences')

/**
 * Unwrap an OCS envelope and turn failures into RequestFailed.
 *
 * @param request - pending axios request
 */
async function ocs<T>(request: Promise<{ data: { ocs: { data: T } } }>): Promise<T> {
	try {
		const { data } = await request
		return data.ocs.data
	} catch (error: unknown) {
		const response = (error as { response?: { status?: number, data?: { ocs?: { data?: { errors?: unknown } } } } }).response
		const errors = response?.data?.ocs?.data?.errors
		throw new RequestFailed(
			response?.status ?? 0,
			Array.isArray(errors) ? errors.filter(isFieldError) : [],
		)
	}
}

/**
 * @param value - one entry of the errors list
 */
function isFieldError(value: unknown): value is FieldError {
	return typeof value === 'object' && value !== null
		&& typeof (value as FieldError).field === 'string'
		&& typeof (value as FieldError).code === 'string'
		&& typeof (value as FieldError).message === 'string'
}

/**
 * @param settings - changed fields only
 */
export function updateAdminSettings(settings: Partial<AdminSettings>): Promise<AdminSettingsResponse> {
	return ocs(axios.patch(adminUrl(), { settings }))
}

/**
 * Restore the shipped defaults.
 */
export function resetAdminSettings(): Promise<AdminSettingsResponse> {
	return ocs(axios.delete(adminUrl()))
}

/**
 * @param preferences - changed fields only
 */
export function updatePreferences(preferences: Partial<UserPreferences>): Promise<PreferencesResponse> {
	return ocs(axios.patch(preferencesUrl(), { preferences }))
}

/**
 * Drop every personal override.
 */
export function resetPreferences(): Promise<PreferencesResponse> {
	return ocs(axios.delete(preferencesUrl()))
}
