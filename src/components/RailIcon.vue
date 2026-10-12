<!--
  SPDX-FileCopyrightText: 2026 André Wiesehoff
  SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<svg
		class="nc-rail-svg"
		viewBox="0 0 24 24"
		:width="size"
		:height="size"
		aria-hidden="true"
		focusable="false">
		<path :d="path" />
	</svg>
</template>

<script setup lang="ts">
withDefaults(defineProps<{
	/** Material Design Icons path data */
	path: string
	/** Rendered size in pixels */
	size?: number
}>(), {
	size: 20,
})
</script>

<style>
.nc-rail-svg {
	flex: none;
	fill: currentColor;
}
</style>
