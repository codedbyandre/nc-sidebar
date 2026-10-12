<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 André Wiesehoff
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Sidebar\Model;

final readonly class HexColor implements \Stringable {
	private const LIGHT_TEXT = '#ffffff';
	private const DARK_TEXT = '#000000';

	private function __construct(
		private string $value,
	) {
	}

	/**
	 * Accepts #rgb and #rrggbb in any case and stores lowercase #rrggbb.
	 *
	 * @throws \InvalidArgumentException
	 */
	public static function parse(string $raw): self {
		$value = strtolower(trim($raw));
		if (preg_match('/^#[0-9a-f]{3}$/', $value) === 1) {
			$value = '#' . $value[1] . $value[1] . $value[2] . $value[2] . $value[3] . $value[3];
		}
		if (preg_match('/^#[0-9a-f]{6}$/', $value) !== 1) {
			throw new \InvalidArgumentException('Expected a hex color like #0082c9');
		}

		return new self($value);
	}

	/**
	 * WCAG 2.x relative luminance, 0 for black and 1 for white.
	 */
	public function luminance(): float {
		$channels = [];
		foreach ([1, 3, 5] as $offset) {
			$channel = hexdec(substr($this->value, $offset, 2)) / 255;
			$channels[] = $channel <= 0.04045 ? $channel / 12.92 : (($channel + 0.055) / 1.055) ** 2.4;
		}

		return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
	}

	public function contrastWith(self $other): float {
		$lighter = max($this->luminance(), $other->luminance());
		$darker = min($this->luminance(), $other->luminance());

		return ($lighter + 0.05) / ($darker + 0.05);
	}

	/**
	 * Text color with the higher contrast. Black or white always reaches at least 4.58:1.
	 */
	public function readableText(): self {
		$light = new self(self::LIGHT_TEXT);
		$dark = new self(self::DARK_TEXT);

		return $this->contrastWith($light) >= $this->contrastWith($dark) ? $light : $dark;
	}

	#[\Override]
	public function __toString(): string {
		return $this->value;
	}
}
