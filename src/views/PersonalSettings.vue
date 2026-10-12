<!--
  SPDX-FileCopyrightText: 2026 André Wiesehoff
  SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="sidebar-settings">
		<NcSettingsSection
			v-if="policy.allowUserColor || policy.allowUserLayout"
			:name="t('sidebar', 'Appearance')"
			:description="t('sidebar', 'How the sidebar looks for you. Default follows your administrator.')">
			<div class="sidebar-settings__split">
				<div class="sidebar-settings__fields">
					<ColorModeField
						v-if="policy.allowUserColor"
						v-model:mode="draft.colorMode"
						v-model:customColor="draft.customColor"
						name="sidebar-personal-color"
						:inheritedLabel="t('sidebar', 'Currently: {mode}', { mode: modeName(policy.colorMode) })"
						:inheritedMode="policy.colorMode"
						:inheritedColor="policy.customColor"
						:error="fieldError('customColor')" />

					<fieldset v-if="policy.allowUserLayout" class="sidebar-settings__group">
						<legend class="sidebar-settings__legend">
							{{ t('sidebar', 'App names under the icons') }}
						</legend>
						<NcCheckboxRadioSwitch
							v-for="option in labelOptions"
							:key="option.value"
							v-model="labelChoice"
							type="radio"
							name="sidebar-personal-labels"
							:value="option.value">
							{{ option.label }}
						</NcCheckboxRadioSwitch>
					</fieldset>
				</div>
				<RailPreview
					class="sidebar-settings__preview"
					:mode="effectiveMode"
					:customColor="effectiveColor"
					:showLabels="effectiveLabels"
					:showLogo="policy.showLogo"
					:apps="previewApps" />
			</div>
		</NcSettingsSection>

		<NcSettingsSection
			v-if="policy.allowUserHiddenApps"
			:name="t('sidebar', 'Apps in the sidebar')"
			:description="t('sidebar', 'Hide apps you rarely use. They stay available by their address, and an app stays visible while it is open.')">
			<AppVisibilityList
				v-model:hidden="draft.hiddenApps"
				:apps="apps"
				:locked="policy.hiddenApps" />
			<p class="sidebar-settings__note">
				<a class="sidebar-settings__link" :href="appOrderUrl">{{ t('sidebar', 'Change the order of your apps') }}</a>
			</p>
		</NcSettingsSection>

		<NcSettingsSection :name="t('sidebar', 'Narrow and wide')">
			<p class="sidebar-settings__note sidebar-settings__note--flush">
				{{ t('sidebar', 'Switch between icons only and icons with names with the button at the bottom of the sidebar, or press Alt+Shift+S. On small screens, the menu button in the header opens the sidebar.') }}
			</p>
		</NcSettingsSection>

		<SaveBar
			:dirty="dirty"
			:busy="busy"
			:saved="savedNotice"
			:error="error"
			:resetLabel="t('sidebar', 'Use defaults')"
			:resetQuestion="t('sidebar', 'Remove all your personal sidebar settings?')"
			@save="save"
			@discard="discard"
			@reset="reset" />
	</div>
</template>

<script setup lang="ts">
import type { PreferencesResponse } from '../api.ts'
import type { AdminSettings, ColorMode, NavigationApp, UserPreferences } from '../types.ts'

import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { computed, ref } from 'vue'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import AppVisibilityList from '../components/settings/AppVisibilityList.vue'
import ColorModeField from '../components/settings/ColorModeField.vue'
import RailPreview from '../components/settings/RailPreview.vue'
import SaveBar from '../components/settings/SaveBar.vue'
import { resetPreferences, updatePreferences } from '../api.ts'
import { useSettingsForm } from '../composables/useSettingsForm.ts'
import { announceRailConfig } from '../events.ts'
import { colorProblems } from '../validation.ts'

type PersonalForm = Pick<UserPreferences, 'colorMode' | 'customColor' | 'showLabels' | 'hiddenApps'>

const apps = loadState<NavigationApp[]>('sidebar', 'navigation-apps', [])
const policy = ref<AdminSettings>(loadState<AdminSettings>('sidebar', 'admin-settings'))
const appOrderUrl = generateUrl('/settings/user/theming')

const { draft, dirty, busy, savedNotice, error, fieldError, submit, discard } = useSettingsForm<PersonalForm>(
	formValues(loadState<UserPreferences>('sidebar', 'preferences')),
	(values) => colorProblems(values.colorMode, values.customColor),
)

const labelOptions = computed(() => [
	{ value: 'default', label: t('sidebar', 'Default ({state})', { state: policy.value.showLabels ? t('sidebar', 'shown') : t('sidebar', 'hidden') }) },
	{ value: 'on', label: t('sidebar', 'Show') },
	{ value: 'off', label: t('sidebar', 'Hide') },
])

const labelChoice = computed<string>({
	get: () => (draft.value.showLabels === null ? 'default' : draft.value.showLabels ? 'on' : 'off'),
	set: (choice) => {
		draft.value.showLabels = choice === 'default' ? null : choice === 'on'
	},
})

const effectiveMode = computed<ColorMode>(() => (policy.value.allowUserColor && draft.value.colorMode !== null ? draft.value.colorMode : policy.value.colorMode))
const effectiveColor = computed(() => (policy.value.allowUserColor && draft.value.colorMode !== null ? draft.value.customColor : policy.value.customColor))
const effectiveLabels = computed(() => (policy.value.allowUserLayout && draft.value.showLabels !== null ? draft.value.showLabels : policy.value.showLabels))
const previewApps = computed(() => apps.filter((app) => !policy.value.hiddenApps.includes(app.id) && !draft.value.hiddenApps.includes(app.id)))

/**
 * @param preferences - stored preferences
 */
function formValues(preferences: UserPreferences): PersonalForm {
	return {
		colorMode: preferences.colorMode,
		customColor: preferences.customColor,
		showLabels: preferences.showLabels,
		hiddenApps: preferences.hiddenApps,
	}
}

/**
 * @param mode - color mode
 */
function modeName(mode: ColorMode): string {
	const names: Record<ColorMode, string> = {
		theme: t('sidebar', 'Theme color'),
		neutral: t('sidebar', 'Neutral'),
		custom: t('sidebar', 'Custom color'),
	}
	return names[mode]
}

/**
 * Only fields the administrator left open are sent; the server rejects the rest.
 */
function changes(): Partial<UserPreferences> {
	const values: Partial<UserPreferences> = {}
	if (policy.value.allowUserColor) {
		values.colorMode = draft.value.colorMode
		values.customColor = draft.value.colorMode === 'custom' ? draft.value.customColor : null
	}
	if (policy.value.allowUserLayout) {
		values.showLabels = draft.value.showLabels
	}
	if (policy.value.allowUserHiddenApps) {
		values.hiddenApps = draft.value.hiddenApps
	}
	return values
}

/**
 * @param response - stored preferences and the resolved rail
 */
function apply(response: PreferencesResponse): PersonalForm {
	policy.value = response.defaults
	announceRailConfig(response.rail)
	return formValues(response.preferences)
}

/**
 * Store the form.
 */
function save(): Promise<boolean> {
	return submit(async () => apply(await updatePreferences(changes())))
}

/**
 * Drop every personal override.
 */
function reset(): Promise<boolean> {
	return submit(async () => apply(await resetPreferences()), false)
}
</script>

<style scoped>
.sidebar-settings__split {
	display: flex;
	flex-wrap: wrap;
	align-items: flex-start;
	gap: 32px;
}

.sidebar-settings__fields {
	display: flex;
	flex: 1 1 420px;
	flex-direction: column;
	gap: 24px;
	min-width: 0;
}

.sidebar-settings__preview {
	flex: none;
}

.sidebar-settings__group {
	min-inline-size: 0;
	margin: 0;
	padding: 0;
	border: none;
}

.sidebar-settings__legend {
	margin-block-end: 6px;
	padding: 0;
	font-weight: 600;
}

.sidebar-settings__note {
	max-width: 640px;
	margin-block-start: 12px;
	color: var(--color-text-maxcontrast);
}

.sidebar-settings__note--flush {
	margin-block-start: 0;
}

.sidebar-settings__link {
	color: var(--color-main-text);
	text-decoration: underline;
	text-underline-offset: 3px;
}
</style>
