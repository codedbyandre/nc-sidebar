<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 André Wiesehoff
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Sidebar\Model;

enum ColorMode: string {
	case Theme = 'theme';
	case Neutral = 'neutral';
	case Custom = 'custom';

	/**
	 * @return list<string>
	 */
	public static function values(): array {
		return array_map(static fn (self $mode): string => $mode->value, self::cases());
	}
}
