<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 André Wiesehoff
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Sidebar\Service;

use OCA\Sidebar\Model\RailConfig;

final readonly class RailConfigService {
	public function __construct(
		private AdminSettingsStore $adminSettings,
		private UserPreferencesStore $userPreferences,
	) {
	}

	public function forUser(string $userId): RailConfig {
		return RailConfig::resolve(
			$this->adminSettings->current(),
			$this->userPreferences->current($userId),
		);
	}
}
