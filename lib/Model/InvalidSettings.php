<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 André Wiesehoff
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Sidebar\Model;

final class InvalidSettings extends \InvalidArgumentException {
	/**
	 * @param list<array{field: string, code: string, message: string}> $errors
	 */
	public function __construct(
		public readonly array $errors,
	) {
		parent::__construct('Invalid settings: ' . implode(', ', array_column($errors, 'field')));
	}
}
