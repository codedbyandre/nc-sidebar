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
use OCA\Sidebar\Model\UserPreferences;
use OCP\Config\IUserConfig;
use Psr\Log\LoggerInterface;

final readonly class UserPreferencesStore {
	public const KEY = 'preferences';

	public function __construct(
		private IUserConfig $config,
		private LoggerInterface $logger,
	) {
	}

	public function current(string $userId): UserPreferences {
		$stored = $this->config->getValueArray($userId, Application::APP_ID, self::KEY);
		try {
			return UserPreferences::defaults()->with($stored, self::unlocked());
		} catch (InvalidSettings $e) {
			$this->logger->warning('Stored sidebar preferences are invalid, using defaults', [
				'userId' => $userId,
				'errors' => $e->errors,
			]);
			return UserPreferences::defaults();
		}
	}

	/**
	 * @param array<array-key, mixed> $changes
	 * @throws InvalidSettings
	 */
	public function update(string $userId, array $changes, AdminSettings $policy): UserPreferences {
		$next = $this->current($userId)->with($changes, $policy);
		$this->config->setValueArray($userId, Application::APP_ID, self::KEY, $next->jsonSerialize());

		return $next;
	}

	public function reset(string $userId): UserPreferences {
		$this->config->deleteUserConfig($userId, Application::APP_ID, self::KEY);

		return UserPreferences::defaults();
	}

	/**
	 * Reading stored values must not apply today's locks: a locked field
	 * is ignored when resolving, not erased.
	 */
	private static function unlocked(): AdminSettings {
		return AdminSettings::defaults()->with([
			'allowUserColor' => true,
			'allowUserLayout' => true,
			'allowUserHiddenApps' => true,
		]);
	}
}
