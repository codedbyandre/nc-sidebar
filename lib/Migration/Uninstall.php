<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 André Wiesehoff
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Sidebar\Migration;

use OCA\Sidebar\AppInfo\Application;
use OCP\Config\IUserConfig;
use OCP\IAppConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

final class Uninstall implements IRepairStep {
	public function __construct(
		private readonly IAppConfig $appConfig,
		private readonly IUserConfig $userConfig,
	) {
	}

	#[\Override]
	public function getName(): string {
		return 'Remove sidebar settings and personal preferences';
	}

	#[\Override]
	public function run(IOutput $output): void {
		$this->appConfig->deleteApp(Application::APP_ID);
		$this->userConfig->deleteApp(Application::APP_ID);
	}
}
