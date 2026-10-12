<!--
  SPDX-FileCopyrightText: 2026 André Wiesehoff
  SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="sidebar-settings">
		<NcSettingsSection
			:name="t('sidebar', 'Appearance')"
			:description="t('sidebar', 'The default look of the sidebar for everyone.')">
			<div class="sidebar-settings__split">
				<div class="sidebar-settings__fields">
					<ColorModeField
						v-model:mode="colorMode"
						v-model:customColor="draft.customColor"
						name="sidebar-admin-color"
						:error="fieldError('customColor')" />

					<div class="sidebar-settings__switches">
						<NcCheckboxRadioSwitch v-model="draft.showLabels" type="switch">
							{{ t('sidebar', 'Show app names under the icons') }}
						</NcCheckboxRadioSwitch>
						<NcCheckboxRadioSwitch v-model="draft.expandedByDefault" type="switch">
							{{ t('sidebar', 'Start with the wide sidebar') }}
						</NcCheckboxRadioSwitch>
						<NcCheckboxRadioSwitch v-model="draft.showLogo" type="switch">
							{{ t('sidebar', 'Show the logo at the top') }}
						</NcCheckboxRadioSwitch>
						<NcCheckboxRadioSwitch v-model="draft.showMoreApps" type="switch">
							{{ t('sidebar', 'Show “More apps” for administrators') }}
						</NcCheckboxRadioSwitch>
					</div>
				</div>
				<RailPreview
					class="sidebar-settings__preview"
					:mode="draft.colorMode"
					:customColor="draft.customColor"
					:showLabels="draft.showLabels"
					:showLogo="draft.showLogo"
					:apps="previewApps" />
			</div>
		</NcSettingsSection>

		<NcSettingsSection
			:name="t('sidebar', 'Apps in the sidebar')"
			:description="t('sidebar', 'Hidden apps disappear from everyone’s sidebar. People can still open them by their address, and an app stays visible while it is open.')">
			<AppVisibilityList v-model:hidden="draft.hiddenApps" :apps="apps" />
		</NcSettingsSection>

		<NcSettingsSection
			:name="t('sidebar', 'Personal settings')"
			:description="t('sidebar', 'Choose what people may change for themselves under Personal settings › Sidebar. Everything you lock uses the defaults above.')">
			<div class="sidebar-settings__switches">
				<NcCheckboxRadioSwitch v-model="draft.allowUserColor" type="switch">
					{{ t('sidebar', 'Choose their own color') }}
				</NcCheckboxRadioSwitch>
				<NcCheckboxRadioSwitch v-model="draft.allowUserLayout" type="switch">
					{{ t('sidebar', 'Show or hide app names') }}
				</NcCheckboxRadioSwitch>
				<NcCheckboxRadioSwitch v-model="draft.allowUserHiddenApps" type="switch">
					{{ t('sidebar', 'Hide additional apps') }}
				</NcCheckboxRadioSwitch>
			</div>
			<p class="sidebar-settings__note">
				{{ t('sidebar', 'Everyone can switch between the narrow and the wide sidebar at any time.') }}
			</p>
		</NcSettingsSection>

		<SaveBar
			:dirty="dirty"
			:busy="busy"
			:saved="savedNotice"
			:error="error"
			:resetLabel="t('sidebar', 'Restore defaults')"
			:resetQuestion="t('sidebar', 'Restore all sidebar defaults?')"
			@save="save"
			@discard="discard"
			@reset="reset" />
	</div>
</template>

<script setup lang="ts">
import type { AdminSettings, ColorMode, NavigationApp } from '../types.ts'

import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { computed } from 'vue'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import AppVisibilityList from '../components/settings/AppVisibilityList.vue'
import ColorModeField from '../components/settings/ColorModeField.vue'
import RailPreview from '../components/settings/RailPreview.vue'
import SaveBar from '../components/settings/SaveBar.vue'
import { resetAdminSettings, updateAdminSettings } from '../api.ts'
import { useSettingsForm } from '../composables/useSettingsForm.ts'
import { announceRailConfig } from '../events.ts'
import { colorProblems } from '../validation.ts'

const apps = loadState<NavigationApp[]>('sidebar', 'navigation-apps', [])

const { draft, dirty, busy, savedNotice, error, fieldError, submit, discard } = useSettingsForm(
	loadState<AdminSettings>('sidebar', 'admin-settings'),
	(values) => colorProblems(values.colorMode, values.customColor),
)

const colorMode = computed<ColorMode | null>({
	get: () => draft.value.colorMode,
	set: (mode) => {
		draft.value.colorMode = mode ?? 'theme'
	},
})

const previewApps = computed(() => apps.filter((app) => !draft.value.hiddenApps.includes(app.id)))

/**
 * Store the whole form.
 */
function save(): Promise<boolean> {
	return submit(async () => {
		const values = { ...draft.value }
		if (values.colorMode !== 'custom') {
			values.customColor = null
		}
		const response = await updateAdminSettings(values)
		if (response.rail !== null) {
			announceRailConfig(response.rail)
		}
		return response.settings
	})
}

/**
 * Restore the shipped defaults.
 */
function reset(): Promise<boolean> {
	return submit(async () => {
		const response = await resetAdminSettings()
		if (response.rail !== null) {
			announceRailConfig(response.rail)
		}
		return response.settings
	}, false)
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

.sidebar-settings__switches {
	display: flex;
	flex-direction: column;
	gap: 2px;
}

.sidebar-settings__note {
	max-width: 640px;
	margin-block-start: 12px;
	color: var(--color-text-maxcontrast);
}
</style>
