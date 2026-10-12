<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 André Wiesehoff
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Sidebar\Service;

use OCA\Sidebar\AppInfo\Application;
use OCA\Sidebar\Model\AdminSettings;
use OCA\Sidebar\Model\InvalidSettings;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;

final readonly class AdminSettingsStore {
	public const KEY = 'settings';

	public function __construct(
		private IAppConfig $config,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * A stored document that no longer validates falls back to the defaults
	 * instead of breaking every page.
	 */
	public function current(): AdminSettings {
		$stored = $this->config->getValueArray(Application::APP_ID, self::KEY);
		try {
			return AdminSettings::defaults()->with($stored);
		} catch (InvalidSettings $e) {
			$this->logger->warning('Stored sidebar settings are invalid, using defaults', ['errors' => $e->errors]);
			return AdminSettings::defaults();
		}
	}

	/**
	 * @param array<array-key, mixed> $changes
	 * @throws InvalidSettings
	 */
	public function update(array $changes): AdminSettings {
		$next = $this->current()->with($changes);
		$this->config->setValueArray(Application::APP_ID, self::KEY, $next->jsonSerialize());

		return $next;
	}

	public function reset(): AdminSettings {
		$this->config->deleteKey(Application::APP_ID, self::KEY);

		return AdminSettings::defaults();
	}
}
