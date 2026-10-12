<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 André Wiesehoff
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Sidebar\Service;

use OCP\INavigationManager;

/**
 * The apps the settings pages offer to show or hide.
 */
final readonly class NavigationApps {
	public function __construct(
		private INavigationManager $navigation,
	) {
	}

	/**
	 * @return list<array{id: string, name: string, icon: string}>
	 */
	public function list(): array {
		$entries = array_values(array_filter(
			$this->navigation->getAll(INavigationManager::TYPE_APPS),
			static fn (array $entry): bool => is_string($entry['id'] ?? null) && is_string($entry['name'] ?? null),
		));
		usort($entries, static fn (array $a, array $b): int => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));

		return array_map(static fn (array $entry): array => [
			'id' => $entry['id'],
			'name' => $entry['name'],
			'icon' => is_string($entry['icon'] ?? null) ? $entry['icon'] : '',
		], $entries);
	}
}
