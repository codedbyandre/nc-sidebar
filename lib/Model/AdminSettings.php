<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 André Wiesehoff
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Sidebar\Model;

/**
 * Instance-wide defaults and what people may change for themselves.
 */
final readonly class AdminSettings implements \JsonSerializable {
	private const KEYS = [
		'colorMode',
		'customColor',
		'showLabels',
		'expandedByDefault',
		'showLogo',
		'showMoreApps',
		'hiddenApps',
		'allowUserColor',
		'allowUserLayout',
		'allowUserHiddenApps',
	];

	public function __construct(
		public ColorMode $colorMode,
		public ?HexColor $customColor,
		public bool $showLabels,
		public bool $expandedByDefault,
		public bool $showLogo,
		public bool $showMoreApps,
		public AppIdList $hiddenApps,
		public bool $allowUserColor,
		public bool $allowUserLayout,
		public bool $allowUserHiddenApps,
	) {
	}

	public static function defaults(): self {
		return new self(
			colorMode: ColorMode::Theme,
			customColor: null,
			showLabels: false,
			expandedByDefault: false,
			showLogo: true,
			showMoreApps: true,
			hiddenApps: AppIdList::empty(),
			allowUserColor: true,
			allowUserLayout: true,
			allowUserHiddenApps: true,
		);
	}

	/**
	 * Applies a partial document. Keys that are absent keep their value.
	 *
	 * @param array<array-key, mixed> $changes
	 * @throws InvalidSettings
	 */
	public function with(array $changes): self {
		$input = new SettingsInput($changes, self::KEYS);
		$next = new self(
			colorMode: $input->colorMode('colorMode', $this->colorMode),
			customColor: $input->color('customColor', $this->customColor),
			showLabels: $input->bool('showLabels', $this->showLabels),
			expandedByDefault: $input->bool('expandedByDefault', $this->expandedByDefault),
			showLogo: $input->bool('showLogo', $this->showLogo),
			showMoreApps: $input->bool('showMoreApps', $this->showMoreApps),
			hiddenApps: $input->appIds('hiddenApps', $this->hiddenApps),
			allowUserColor: $input->bool('allowUserColor', $this->allowUserColor),
			allowUserLayout: $input->bool('allowUserLayout', $this->allowUserLayout),
			allowUserHiddenApps: $input->bool('allowUserHiddenApps', $this->allowUserHiddenApps),
		);
		if ($next->colorMode === ColorMode::Custom && $next->customColor === null && !$input->hasError('customColor')) {
			$input->fail('customColor', 'required', 'A custom color needs a hex value');
		}
		$input->throwIfInvalid();

		return $next;
	}

	/**
	 * @return array{colorMode: string, customColor: ?string, showLabels: bool, expandedByDefault: bool, showLogo: bool, showMoreApps: bool, hiddenApps: list<string>, allowUserColor: bool, allowUserLayout: bool, allowUserHiddenApps: bool}
	 */
	#[\Override]
	public function jsonSerialize(): array {
		return [
			'colorMode' => $this->colorMode->value,
			'customColor' => $this->customColor === null ? null : (string)$this->customColor,
			'showLabels' => $this->showLabels,
			'expandedByDefault' => $this->expandedByDefault,
			'showLogo' => $this->showLogo,
			'showMoreApps' => $this->showMoreApps,
			'hiddenApps' => $this->hiddenApps->toList(),
			'allowUserColor' => $this->allowUserColor,
			'allowUserLayout' => $this->allowUserLayout,
			'allowUserHiddenApps' => $this->allowUserHiddenApps,
		];
	}
}
