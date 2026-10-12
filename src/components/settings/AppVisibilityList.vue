<!--
  SPDX-FileCopyrightText: 2026 André Wiesehoff
  SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="sidebar-apps">
		<div class="sidebar-apps__toolbar">
			<NcTextField
				v-if="apps.length > 8"
				class="sidebar-apps__filter"
				:label="t('sidebar', 'Filter apps')"
				:modelValue="query"
				:showTrailingButton="query !== ''"
				@trailingButtonClick="query = ''"
				@update:modelValue="query = String($event)" />
			<p class="sidebar-apps__summary">
				{{ n('sidebar', '{visible} of {total} app visible', '{visible} of {total} apps visible', apps.length, { visible: visibleCount, total: apps.length }) }}
			</p>
		</div>
		<ul class="sidebar-apps__list">
			<li
				v-for="app in filtered"
				:key="app.id"
				class="sidebar-apps__row"
				:class="{ 'sidebar-apps__row--hidden': isHidden(app.id) }">
				<span class="sidebar-apps__icon-wrap" aria-hidden="true">
					<span class="sidebar-apps__icon" :style="iconMask(app.icon)" />
				</span>
				<span class="sidebar-apps__name">
					{{ app.name }}
					<span v-if="locked.includes(app.id)" class="sidebar-apps__note">
						{{ t('sidebar', 'Hidden by your administrator') }}
					</span>
				</span>
				<NcCheckboxRadioSwitch
					type="switch"
					:modelValue="!isHidden(app.id)"
					:disabled="locked.includes(app.id)"
					:aria-label="t('sidebar', 'Show {app} in the sidebar', { app: app.name })"
					@update:modelValue="toggle(app.id, $event)" />
			</li>
		</ul>
		<p v-if="filtered.length === 0" class="sidebar-apps__empty">
			{{ t('sidebar', 'No app matches “{query}”.', { query }) }}
		</p>
	</div>
</template>

<script setup lang="ts">
import type { NavigationApp } from '../../types.ts'

import { n, t } from '@nextcloud/l10n'
import { computed, ref } from 'vue'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import { iconMask } from '../../theme.ts'

const props = withDefaults(defineProps<{
	/** Apps in navigation order */
	apps: NavigationApp[]
	/** Ids hidden by this form */
	hidden: string[]
	/** Ids hidden at a higher level that this form cannot change */
	locked?: string[]
}>(), {
	locked: () => [],
})

const emit = defineEmits<{
	'update:hidden': [ids: string[]]
}>()

const query = ref('')

const filtered = computed(() => {
	const needle = query.value.trim().toLocaleLowerCase()
	return needle === ''
		? props.apps
		: props.apps.filter((app) => app.name.toLocaleLowerCase().includes(needle))
})

const visibleCount = computed(() => props.apps.filter((app) => !isHidden(app.id)).length)

/**
 * @param id - app id
 */
function isHidden(id: string): boolean {
	return props.locked.includes(id) || props.hidden.includes(id)
}

/**
 * @param id - app id
 * @param visible - new switch state
 */
function toggle(id: string, visible: boolean): void {
	const rest = props.hidden.filter((candidate) => candidate !== id)
	emit('update:hidden', visible ? rest : [...rest, id])
}
</script>

<style scoped>
.sidebar-apps {
	max-width: 640px;
}

.sidebar-apps__toolbar {
	display: flex;
	flex-wrap: wrap;
	align-items: flex-end;
	justify-content: space-between;
	gap: 8px 16px;
	margin-block-end: 8px;
}

.sidebar-apps__filter {
	flex: 1 1 240px;
	max-width: 320px;
}

.sidebar-apps__summary {
	margin: 0;
	color: var(--color-text-maxcontrast);
	font-variant-numeric: tabular-nums;
}

.sidebar-apps__list {
	margin: 0;
	padding: 0;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius-large, 12px);
	list-style: none;
}

.sidebar-apps__row {
	display: flex;
	align-items: center;
	gap: 12px;
	min-height: 52px;
	padding-inline: 12px 6px;
}

.sidebar-apps__row + .sidebar-apps__row {
	border-block-start: 1px solid var(--color-border);
}

.sidebar-apps__icon-wrap {
	display: inline-flex;
	flex: none;
	align-items: center;
	justify-content: center;
	width: 32px;
	height: 32px;
	border-radius: 8px;
	background-color: var(--color-primary);
	color: var(--color-primary-text);
}

.sidebar-apps__icon {
	width: 20px;
	height: 20px;
	background-color: currentColor;
	mask-repeat: no-repeat;
	mask-position: center;
	mask-size: contain;
}

.sidebar-apps__row--hidden .sidebar-apps__icon-wrap {
	background-color: var(--color-background-darker);
	color: var(--color-text-maxcontrast);
}

.sidebar-apps__name {
	display: flex;
	flex: 1 1 auto;
	flex-direction: column;
	min-width: 0;
}

.sidebar-apps__row--hidden .sidebar-apps__name {
	color: var(--color-text-maxcontrast);
}

.sidebar-apps__note {
	color: var(--color-text-maxcontrast);
	font-size: var(--font-size-small, 13px);
}

.sidebar-apps__empty {
	margin: 12px 0 0;
	color: var(--color-text-maxcontrast);
}
</style>
