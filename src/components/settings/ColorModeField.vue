<!--
  SPDX-FileCopyrightText: 2026 André Wiesehoff
  SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<fieldset class="sidebar-color">
		<legend class="sidebar-color__legend">
			{{ t('sidebar', 'Sidebar color') }}
		</legend>
		<div class="sidebar-color__options">
			<label
				v-for="option in options"
				:key="option.id"
				class="sidebar-color__option"
				:class="{ 'sidebar-color__option--checked': option.id === selectedId }">
				<input
					class="sidebar-color__input"
					type="radio"
					:name="name"
					:value="option.id"
					:checked="option.id === selectedId"
					@change="select(option.id)">
				<span class="sidebar-color__swatch" :style="swatchStyle(option.mode)" aria-hidden="true">
					<span class="sidebar-color__swatch-line" />
					<span class="sidebar-color__swatch-line sidebar-color__swatch-line--short" />
				</span>
				<span class="sidebar-color__text">
					<span class="sidebar-color__name">{{ option.name }}</span>
					<span class="sidebar-color__hint">{{ option.hint }}</span>
				</span>
			</label>
		</div>

		<div v-if="mode === 'custom'" class="sidebar-color__custom">
			<NcColorPicker
				:modelValue="pickerValue"
				:advancedFields="true"
				@update:modelValue="emit('update:customColor', $event)">
				<NcButton :aria-label="t('sidebar', 'Pick a color')">
					<template #icon>
						<span class="sidebar-color__chip" :style="{ backgroundColor: pickerValue }" />
					</template>
					{{ t('sidebar', 'Pick a color') }}
				</NcButton>
			</NcColorPicker>
			<NcTextField
				class="sidebar-color__hex"
				:label="t('sidebar', 'Hex value')"
				:modelValue="customColor ?? ''"
				:error="error !== null"
				:helperText="error ?? contrastNote"
				placeholder="#0082c9"
				maxlength="7"
				spellcheck="false"
				autocomplete="off"
				@update:modelValue="emit('update:customColor', String($event))" />
		</div>
	</fieldset>
</template>

<script setup lang="ts">
import type { ColorMode } from '../../types.ts'

import { t } from '@nextcloud/l10n'
import { computed } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcColorPicker from '@nextcloud/vue/components/NcColorPicker'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import { contrast, normalizeHex, readableText, themeVariables } from '../../theme.ts'

const props = withDefaults(defineProps<{
	/** Selected mode; null selects the inherited option */
	mode: ColorMode | null
	/** Hex value for the custom mode */
	customColor: string | null
	/** Radio group name */
	name: string
	/** Offer "use the administrator's choice" and describe it */
	inheritedLabel?: string | null
	/** Mode and color behind the inherited option, for its swatch */
	inheritedMode?: ColorMode
	inheritedColor?: string | null
	/** Validation message for the hex field */
	error?: string | null
}>(), {
	inheritedLabel: null,
	inheritedMode: 'theme',
	inheritedColor: null,
	error: null,
})

const emit = defineEmits<{
	'update:mode': [mode: ColorMode | null]
	'update:customColor': [color: string]
}>()

interface Option {
	id: string
	mode: ColorMode | null
	name: string
	hint: string
}

const options = computed<Option[]>(() => {
	const list: Option[] = [
		{ id: 'theme', mode: 'theme', name: t('sidebar', 'Theme color'), hint: t('sidebar', 'Follows the color set under Theming') },
		{ id: 'neutral', mode: 'neutral', name: t('sidebar', 'Neutral'), hint: t('sidebar', 'Page background, adapts to light and dark mode') },
		{ id: 'custom', mode: 'custom', name: t('sidebar', 'Custom color'), hint: t('sidebar', 'Any color, text contrast is chosen for you') },
	]
	if (props.inheritedLabel !== null) {
		list.unshift({ id: 'inherit', mode: null, name: t('sidebar', 'Default'), hint: props.inheritedLabel })
	}
	return list
})

const selectedId = computed(() => props.mode ?? 'inherit')

const pickerValue = computed(() => normalizeHex(props.customColor ?? '') ?? '#0082c9')

const contrastNote = computed(() => {
	const hex = normalizeHex(props.customColor ?? '')
	if (hex === null) {
		return ''
	}
	const ratio = contrast(hex, readableText(hex)).toFixed(1)
	return t('sidebar', 'Text contrast {ratio}:1', { ratio })
})

/**
 * @param id - option id
 */
function select(id: string): void {
	const option = options.value.find((candidate) => candidate.id === id)
	if (option === undefined) {
		return
	}
	emit('update:mode', option.mode)
	if (option.mode === 'custom' && (props.customColor === null || props.customColor === '')) {
		emit('update:customColor', '#0082c9')
	}
}

/**
 * @param mode - mode the swatch shows; null shows the inherited one
 */
function swatchStyle(mode: ColorMode | null): Record<string, string> {
	const shown = mode ?? props.inheritedMode
	const color = mode === null ? props.inheritedColor : props.customColor
	const variables = themeVariables(shown, color)
	return {
		backgroundColor: variables['--nc-sidebar-background'],
		color: variables['--nc-sidebar-text'],
		borderColor: shown === 'neutral' ? 'var(--color-border-dark)' : 'transparent',
	}
}
</script>

<style scoped>
.sidebar-color {
	min-inline-size: 0;
	margin: 0;
	padding: 0;
	border: none;
}

.sidebar-color__legend {
	margin-block-end: 10px;
	padding: 0;
	font-weight: 600;
}

.sidebar-color__options {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
	gap: 8px;
	max-width: 760px;
}

.sidebar-color__option {
	position: relative;
	display: flex;
	align-items: center;
	gap: 12px;
	padding: 10px 12px;
	border: 2px solid var(--color-border);
	border-radius: var(--border-radius-large, 12px);
	background-color: var(--color-main-background);
	cursor: pointer;
}

.sidebar-color__option:hover {
	border-color: var(--color-border-dark);
}

.sidebar-color__option--checked,
.sidebar-color__option--checked:hover {
	border-color: var(--color-primary-element);
	background-color: var(--color-primary-element-light);
}

.sidebar-color__option:has(:focus-visible) {
	outline: 2px solid var(--color-main-text);
	outline-offset: 2px;
}

.sidebar-color__input {
	position: absolute;
	opacity: 0;
	pointer-events: none;
}

.sidebar-color__swatch {
	display: flex;
	flex: none;
	flex-direction: column;
	gap: 4px;
	justify-content: center;
	box-sizing: border-box;
	width: 28px;
	height: 40px;
	padding-inline: 7px;
	border: 1px solid transparent;
	border-radius: 8px;
}

.sidebar-color__swatch-line {
	display: block;
	height: 3px;
	border-radius: 2px;
	background-color: currentColor;
}

.sidebar-color__swatch-line--short {
	width: 60%;
	opacity: 0.7;
}

.sidebar-color__text {
	display: flex;
	flex-direction: column;
	min-width: 0;
}

.sidebar-color__name {
	font-weight: 600;
}

.sidebar-color__hint {
	color: var(--color-text-maxcontrast);
	font-size: var(--font-size-small, 13px);
	line-height: 1.35;
}

.sidebar-color__custom {
	display: flex;
	flex-wrap: wrap;
	align-items: flex-start;
	gap: 12px;
	margin-block-start: 14px;
}

.sidebar-color__chip {
	display: block;
	width: 18px;
	height: 18px;
	border-radius: 50%;
	box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.15);
}

.sidebar-color__hex {
	flex: 0 1 200px;
	margin-block-start: 0 !important;
}
</style>
