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
use OCA\Sidebar\Service\UserPreferencesStore;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IL10N;
use OCP\IUserSession;
use OCP\Settings\ISettings;
use OCP\Util;

final class Personal implements ISettings {
	public function __construct(
		private readonly AdminSettingsStore $adminSettings,
		private readonly UserPreferencesStore $preferences,
		private readonly NavigationApps $apps,
		private readonly IUserSession $userSession,
		private readonly IInitialState $initialState,
		private readonly IL10N $l10n,
	) {
	}

	#[\Override]
	public function getForm(): TemplateResponse {
		$userId = $this->userSession->getUser()?->getUID() ?? '';
		$this->initialState->provideInitialState('preferences', $this->preferences->current($userId));
		$this->initialState->provideInitialState('admin-settings', $this->adminSettings->current());
		$this->initialState->provideInitialState('navigation-apps', $this->apps->list());

		Util::addScript(Application::APP_ID, Application::APP_ID . '-personal');
		Util::addStyle(Application::APP_ID, Application::APP_ID . '-personal');

		return new TemplateResponse(Application::APP_ID, 'settings', [
			'mountId' => 'sidebar-personal-settings',
			'noscript' => $this->l10n->t('JavaScript is required to change the sidebar settings.'),
		], TemplateResponse::RENDER_AS_BLANK);
	}

	/**
	 * Hidden entirely when the administrator allows no personal changes.
	 */
	#[\Override]
	public function getSection(): ?string {
		$policy = $this->adminSettings->current();
		if (!$policy->allowUserColor && !$policy->allowUserLayout && !$policy->allowUserHiddenApps) {
			return null;
		}

		return Section::ID;
	}

	#[\Override]
	public function getPriority(): int {
		return 50;
	}
}
