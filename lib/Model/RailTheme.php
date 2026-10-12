<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 André Wiesehoff
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Sidebar\Model;

/**
 * CSS custom properties for one color mode. Values are either references
 * to Nextcloud theming variables or a validated hex color, never free text,
 * because they end up in an inline style element.
 */
final readonly class RailTheme implements \JsonSerializable {
	/**
	 * @param array<string, string> $variables
	 */
	private function __construct(
		public ColorMode $mode,
		private array $variables,
	) {
	}

	public static function for(ColorMode $mode, ?HexColor $custom): self {
		if ($mode === ColorMode::Custom && $custom !== null) {
			return new self($mode, [
				'--nc-sidebar-background' => (string)$custom,
				'--nc-sidebar-text' => (string)$custom->readableText(),
				'--nc-sidebar-border' => 'transparent',
			]);
		}
		if ($mode === ColorMode::Neutral) {
			return new self($mode, [
				'--nc-sidebar-background' => 'var(--color-main-background)',
				'--nc-sidebar-text' => 'var(--color-main-text)',
				'--nc-sidebar-border' => 'var(--color-border)',
			]);
		}

		return new self(ColorMode::Theme, [
			'--nc-sidebar-background' => 'var(--color-primary)',
			'--nc-sidebar-text' => 'var(--color-primary-text)',
			'--nc-sidebar-border' => 'transparent',
		]);
	}

	/**
	 * @return array<string, string>
	 */
	public function variables(): array {
		return $this->variables;
	}

	/**
	 * @return array{mode: string, variables: array<string, string>}
	 */
	#[\Override]
	public function jsonSerialize(): array {
		return [
			'mode' => $this->mode->value,
			'variables' => $this->variables,
		];
	}
}
