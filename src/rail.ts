// SPDX-FileCopyrightText: 2026 André Wiesehoff
// SPDX-License-Identifier: AGPL-3.0-or-later

import { createApp } from 'vue'
import AppRail from './components/AppRail.vue'

const header = document.getElementById('header')
const headerStart = header?.querySelector('.header-start')

if (header && headerStart && !header.classList.contains('header-guest')) {
	const toggleSlot = document.createElement('span')
	toggleSlot.id = 'nc-sidebar-toggle-slot'
	headerStart.prepend(toggleSlot)

	const currentSlot = document.createElement('span')
	currentSlot.id = 'nc-sidebar-current-slot'
	const appMenu = headerStart.querySelector('.app-menu, #header-start__appmenu')
	if (appMenu) {
		appMenu.before(currentSlot)
	} else {
		headerStart.append(currentSlot)
	}

	const host = document.createElement('div')
	host.id = 'nc-sidebar-rail'
	header.after(host)
	createApp(AppRail).mount(host)
}
