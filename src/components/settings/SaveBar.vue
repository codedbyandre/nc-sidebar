<!--
  SPDX-FileCopyrightText: 2026 André Wiesehoff
  SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="sidebar-save" :class="{ 'sidebar-save--dirty': dirty }">
		<div class="sidebar-save__status" role="status" aria-live="polite">
			<span v-if="error" class="sidebar-save__message sidebar-save__message--error">{{ error }}</span>
			<span v-else-if="saved" class="sidebar-save__message sidebar-save__message--success">{{ t('sidebar', 'Saved. The sidebar shows the new settings.') }}</span>
			<span v-else-if="dirty" class="sidebar-save__message">{{ t('sidebar', 'You have unsaved changes.') }}</span>
		</div>
		<div class="sidebar-save__actions">
			<template v-if="confirmingReset">
				<span class="sidebar-save__confirm">{{ resetQuestion }}</span>
				<NcButton variant="error" :disabled="busy" @click="confirmReset">
					{{ resetLabel }}
				</NcButton>
				<NcButton variant="tertiary" :disabled="busy" @click="confirmingReset = false">
					{{ t('sidebar', 'Cancel') }}
				</NcButton>
			</template>
			<template v-else>
				<NcButton variant="tertiary" :disabled="busy" @click="confirmingReset = true">
					{{ resetLabel }}
				</NcButton>
				<NcButton variant="secondary" :disabled="busy || !dirty" @click="emit('discard')">
					{{ t('sidebar', 'Discard changes') }}
				</NcButton>
				<NcButton variant="primary" :disabled="busy || !dirty" @click="emit('save')">
					{{ busy ? t('sidebar', 'Saving…') : t('sidebar', 'Save') }}
				</NcButton>
			</template>
		</div>
	</div>
</template>

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'

defineProps<{
	/** The form differs from what is saved */
	dirty: boolean
	/** A request is running */
	busy: boolean
	/** The last save succeeded */
	saved: boolean
	/** Message of the last failure */
	error: string | null
	/** Text of the reset action */
	resetLabel: string
	/** Question shown before resetting */
	resetQuestion: string
}>()

const emit = defineEmits<{
	save: []
	discard: []
	reset: []
}>()

const confirmingReset = ref(false)

/**
 * Second click of the two-step reset.
 */
function confirmReset(): void {
	confirmingReset.value = false
	emit('reset')
}
</script>

<style scoped>
.sidebar-save {
	position: sticky;
	z-index: 10;
	inset-block-end: 0;
	display: flex;
	flex-wrap: wrap;
	align-items: center;
	justify-content: space-between;
	gap: 12px 24px;
	margin-block-start: 8px;
	padding: 12px 30px;
	border-block-start: 1px solid var(--color-border);
	background-color: var(--color-main-background);
}

.sidebar-save--dirty {
	box-shadow: 0 -6px 16px -8px rgba(0, 0, 0, 0.18);
}

.sidebar-save__status {
	flex: 1 1 240px;
	min-height: 20px;
}

.sidebar-save__message {
	color: var(--color-text-maxcontrast);
}

.sidebar-save__message--error {
	color: var(--color-error-text, var(--color-error));
	font-weight: 600;
}

.sidebar-save__message--success {
	color: var(--color-success-text, var(--color-success));
	font-weight: 600;
}

.sidebar-save__actions {
	display: flex;
	flex-wrap: wrap;
	align-items: center;
	gap: 8px;
}

.sidebar-save__confirm {
	font-weight: 600;
}
</style>
