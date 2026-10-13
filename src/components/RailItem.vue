<!--
  SPDX-FileCopyrightText: 2026 André Wiesehoff
  SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<li class="nc-rail-item">
		<a
			class="nc-rail-item__link"
			:class="{ 'nc-rail-item__link--active': active }"
			:href="normalizedHref"
			:target="external ? '_blank' : undefined"
			:rel="external ? 'noopener noreferrer' : undefined"
			:aria-current="active ? 'page' : undefined"
			:aria-label="accessibleName"
			@pointerenter="onPointerEnter"
			@pointerleave="emit('unhint')"
			@focus="onFocus"
			@blur="emit('unhint')">
			<span class="nc-rail-item__icon">
				<RailIcon v-if="iconPath" :path="iconPath" />
				<span v-else class="nc-rail-item__image" :style="maskStyle" />
				<span v-if="unread > 0" class="nc-rail-item__badge" aria-hidden="true">{{ badge }}</span>
			</span>
			<span ref="labelElement" class="nc-rail-item__label">{{ name }}</span>
			<span v-if="unread > 0" class="nc-rail-item__count" aria-hidden="true">{{ badge }}</span>
		</a>
	</li>
</template>

<script setup lang="ts">
import { n } from '@nextcloud/l10n'
import { computed, ref } from 'vue'
import RailIcon from './RailIcon.vue'
import { iconMask, safeHref } from '../theme.ts'

const props = withDefaults(defineProps<{
	/** Link target */
	href: string
	/** App name, also the accessible name */
	name: string
	/** Image URL of an app icon */
	icon?: string
	/** Material Design Icons path, used instead of an image */
	iconPath?: string
	/** The current page belongs to this entry */
	active?: boolean
	/** Unread items reported by the app */
	unread?: number
	/** Opens in a new tab */
	external?: boolean
}>(), {
	icon: '',
	iconPath: '',
	active: false,
	unread: 0,
	external: false,
})

const emit = defineEmits<{
	/** Pointer or keyboard focus reached the link */
	hint: [target: HTMLElement, label: HTMLElement | null, immediate: boolean]
	/** Pointer or focus left the link */
	unhint: []
}>()

const labelElement = ref<HTMLElement | null>(null)
const normalizedHref = computed(() => safeHref(props.href))
const maskStyle = computed(() => iconMask(props.icon))

const badge = computed(() => (props.unread > 99 ? '99+' : String(props.unread)))

const accessibleName = computed(() => {
	if (props.unread <= 0) {
		return props.name
	}
	return `${props.name}, ${n('sidebar', '{count} notification', '{count} notifications', props.unread, { count: props.unread })}`
})

/**
 * @param event - pointer entering the link
 */
function onPointerEnter(event: PointerEvent): void {
	if (event.pointerType === 'mouse' || event.pointerType === 'pen') {
		emit('hint', event.currentTarget as HTMLElement, labelElement.value, false)
	}
}

/**
 * Keyboard focus shows the tooltip at once; a click's focus does not.
 *
 * @param event - focus on the link
 */
function onFocus(event: FocusEvent): void {
	const target = event.currentTarget as HTMLElement
	if (target.matches(':focus-visible')) {
		emit('hint', target, labelElement.value, true)
	}
}
</script>
