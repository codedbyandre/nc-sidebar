<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 André Wiesehoff
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Sidebar\Listener;

use OCA\Sidebar\AppInfo\Application;
use OCA\Sidebar\Service\RailConfigService;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IUserSession;
use OCP\Util;

/**
 * @template-implements IEventListener<BeforeTemplateRenderedEvent>
 */
final class BeforeTemplateRenderedListener implements IEventListener {
	public function __construct(
		private readonly RailConfigService $railConfig,
		private readonly IUserSession $userSession,
		private readonly IInitialState $initialState,
	) {
	}

	#[\Override]
	public function handle(Event $event): void {
		if (!$event instanceof BeforeTemplateRenderedEvent || !$event->isLoggedIn()) {
			return;
		}
		if ($event->getResponse()->getRenderAs() !== TemplateResponse::RENDER_AS_USER) {
			return;
		}
		$userId = $this->userSession->getUser()?->getUID();
		if ($userId === null) {
			return;
		}

		$config = $this->railConfig->forUser($userId);
		$this->initialState->provideInitialState('rail', $config);
		Util::addHeader('style', ['id' => 'nc-sidebar-variables'], $config->css());
		Util::addScript(Application::APP_ID, Application::APP_ID . '-rail');
		Util::addStyle(Application::APP_ID, Application::APP_ID . '-rail');
	}
}
