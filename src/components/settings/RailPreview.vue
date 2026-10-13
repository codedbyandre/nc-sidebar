<!--
  SPDX-FileCopyrightText: 2026 André Wiesehoff
  SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<figure class="sidebar-preview">
		<div class="sidebar-preview__stage" aria-hidden="true">
			<div
				class="sidebar-preview__rail"
				:class="{ 'sidebar-preview__rail--labeled': showLabels }"
				:style="variables">
				<span v-if="showLogo" class="sidebar-preview__logo" />
				<span
					v-for="(app, index) in sample"
					:key="app.id"
					class="sidebar-preview__item"
					:class="{ 'sidebar-preview__item--active': index === 0 }">
					<span class="sidebar-preview__icon" :style="iconMask(app.icon)" />
					<span v-if="showLabels" class="sidebar-preview__label">{{ app.name }}</span>
				</span>
			</div>
			<div class="sidebar-preview__page">
				<span class="sidebar-preview__bar" />
				<span class="sidebar-preview__bar sidebar-preview__bar--short" />
				<span class="sidebar-preview__block" />
			</div>
		</div>
		<figcaption class="sidebar-preview__caption">
			{{ t('sidebar', 'Preview, not saved yet') }}
		</figcaption>
	</figure>
</template>

<script setup lang="ts">
import type { ColorMode, NavigationApp } from '../../types.ts'

import { t } from '@nextcloud/l10n'
import { computed } from 'vue'
import { iconMask, themeVariables } from '../../theme.ts'

const props = defineProps<{
	/** Color mode to show */
	mode: ColorMode
	/** Hex value for the custom mode */
	customColor: string | null
	/** Names under the icons */
	showLabels: boolean
	/** Logo at the top */
	showLogo: boolean
	/** Apps that remain visible */
	apps: NavigationApp[]
}>()

const variables = computed(() => themeVariables(props.mode, props.customColor))
const sample = computed(() => props.apps.slice(0, 5))
</script>

<style scoped>
.sidebar-preview {
	margin: 0;
}

.sidebar-preview__stage {
	display: flex;
	overflow: hidden;
	width: 260px;
	height: 300px;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius-large, 12px);
	background-color: var(--color-background-dark);
}

.sidebar-preview__rail {
	display: flex;
	flex: none;
	flex-direction: column;
	align-items: center;
	gap: 4px;
	box-sizing: border-box;
	width: 58px;
	padding-block: 10px;
	border-inline-end: 1px solid var(--nc-sidebar-border);
	background-color: var(--nc-sidebar-background);
	color: var(--nc-sidebar-text);
}

.sidebar-preview__rail--labeled {
	width: 78px;
}

.sidebar-preview__logo {
	width: 34px;
	height: 20px;
	margin-block-end: 8px;
	border-radius: 8px;
	background-color: currentColor;
	opacity: 0.85;
}

.sidebar-preview__rail--labeled .sidebar-preview__logo {
	width: 46px;
	height: 22px;
}

.sidebar-preview__item {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	gap: 3px;
	box-sizing: border-box;
	width: 40px;
	min-height: 38px;
	border-radius: 10px;
}

.sidebar-preview__rail--labeled .sidebar-preview__item {
	width: 66px;
	min-height: 44px;
	padding: 4px 3px;
}

.sidebar-preview__item--active {
	background-color: color-mix(in srgb, currentColor 22%, transparent);
}

.sidebar-preview__icon {
	width: 18px;
	height: 18px;
	background-color: currentColor;
	mask-repeat: no-repeat;
	mask-position: center;
	mask-size: contain;
}

.sidebar-preview__label {
	overflow: hidden;
	max-width: 100%;
	font-size: 9px;
	line-height: 1.2;
	text-overflow: ellipsis;
	white-space: nowrap;
}

.sidebar-preview__page {
	display: flex;
	flex: 1 1 auto;
	flex-direction: column;
	gap: 8px;
	margin: 12px;
	padding: 14px;
	border-radius: 10px;
	background-color: var(--color-main-background);
}

.sidebar-preview__bar {
	height: 8px;
	border-radius: 4px;
	background-color: var(--color-background-darker);
}

.sidebar-preview__bar--short {
	width: 55%;
}

.sidebar-preview__block {
	flex: 1 1 auto;
	margin-block-start: 6px;
	border-radius: 8px;
	background-color: var(--color-background-hover);
}

.sidebar-preview__caption {
	margin-block-start: 8px;
	color: var(--color-text-maxcontrast);
	font-size: var(--font-size-small, 13px);
}
</style>
