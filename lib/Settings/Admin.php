<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 André Wiesehoff
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Sidebar\Settings;

use OCA\Sidebar\AppInfo\Application;
use OCA\Sidebar\Service\AdminSettingsStore;
use OCA\Sidebar\Service\NavigationApps;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IL10N;
use OCP\Settings\ISettings;
use OCP\Util;

final class Admin implements ISettings {
	public function __construct(
		private readonly AdminSettingsStore $store,
		private readonly NavigationApps $apps,
		private readonly IInitialState $initialState,
		private readonly IL10N $l10n,
	) {
	}

	#[\Override]
	public function getForm(): TemplateResponse {
		$this->initialState->provideInitialState('admin-settings', $this->store->current());
		$this->initialState->provideInitialState('navigation-apps', $this->apps->list());

		Util::addScript(Application::APP_ID, Application::APP_ID . '-admin');
		Util::addStyle(Application::APP_ID, Application::APP_ID . '-admin');

		return new TemplateResponse(Application::APP_ID, 'settings', [
			'mountId' => 'sidebar-admin-settings',
			'noscript' => $this->l10n->t('JavaScript is required to change the sidebar settings.'),
		], TemplateResponse::RENDER_AS_BLANK);
	}

	#[\Override]
	public function getSection(): string {
		return Section::ID;
	}

	#[\Override]
	public function getPriority(): int {
		return 50;
	}
}
