<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 André Wiesehoff
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Sidebar\Model;

/**
 * One person's overrides. Null means "use the administrator's default".
 *
 * Apps follow the administrator unless they are in one of the three lists:
 * hidden, collapsed under "More", or pinned, which shows an app the
 * administrator collapsed. Hidden wins over collapsed, collapsed over pinned.
 */
final readonly class UserPreferences implements \JsonSerializable {
	private const KEYS = [
		'colorMode',
		'customColor',
		'showLabels',
		'keepAppMenu',
		'hiddenApps',
		'collapsedApps',
		'pinnedApps',
		'expanded',
		'moreOpen',
		'railHidden',
	];

	public function __construct(
		public ?ColorMode $colorMode,
		public ?HexColor $customColor,
		public ?bool $showLabels,
		public ?bool $keepAppMenu,
		public AppIdList $hiddenApps,
		public AppIdList $collapsedApps,
		public AppIdList $pinnedApps,
		public ?bool $expanded,
		public ?bool $moreOpen,
		public ?bool $railHidden,
	) {
	}

	public static function defaults(): self {
		return new self(null, null, null, null, AppIdList::empty(), AppIdList::empty(), AppIdList::empty(), null, null, null);
	}

	/**
	 * Applies a partial document. With a policy, fields the administrator
	 * locked are rejected. Whether the rail is wide, hidden, or shows the
	 * apps under "More" is view state and never locked.
	 *
	 * @param array<array-key, mixed> $changes
	 * @throws InvalidSettings
	 */
	public function with(array $changes, ?AdminSettings $policy = null): self {
		$input = new SettingsInput($changes, self::KEYS);
		$locked = $policy === null ? [] : [
			'colorMode' => !$policy->allowUserColor,
			'customColor' => !$policy->allowUserColor,
			'showLabels' => !$policy->allowUserLayout,
			'keepAppMenu' => !$policy->allowUserLayout,
			'hiddenApps' => !$policy->allowUserHiddenApps,
			'collapsedApps' => !$policy->allowUserHiddenApps,
			'pinnedApps' => !$policy->allowUserHiddenApps,
		];
		foreach ($locked as $key => $isLocked) {
			if ($isLocked && array_key_exists($key, $changes)) {
				$input->fail($key, 'locked', 'This setting is managed by your administrator');
			}
		}
		$next = new self(
			colorMode: $input->nullableColorMode('colorMode', $this->colorMode),
			customColor: $input->color('customColor', $this->customColor),
			showLabels: $input->nullableBool('showLabels', $this->showLabels),
			keepAppMenu: $input->nullableBool('keepAppMenu', $this->keepAppMenu),
			hiddenApps: $input->appIds('hiddenApps', $this->hiddenApps),
			collapsedApps: $input->appIds('collapsedApps', $this->collapsedApps),
			pinnedApps: $input->appIds('pinnedApps', $this->pinnedApps),
			expanded: $input->nullableBool('expanded', $this->expanded),
			moreOpen: $input->nullableBool('moreOpen', $this->moreOpen),
			railHidden: $input->nullableBool('railHidden', $this->railHidden),
		);
		if ($next->colorMode === ColorMode::Custom && $next->customColor === null && !$input->hasError('customColor')) {
			$input->fail('customColor', 'required', 'A custom color needs a hex value');
		}
		$input->throwIfInvalid();

		return $next;
	}

	/**
	 * @return array{colorMode: ?string, customColor: ?string, showLabels: ?bool, keepAppMenu: ?bool, hiddenApps: list<string>, collapsedApps: list<string>, pinnedApps: list<string>, expanded: ?bool, moreOpen: ?bool, railHidden: ?bool}
	 */
	#[\Override]
	public function jsonSerialize(): array {
		return [
			'colorMode' => $this->colorMode?->value,
			'customColor' => $this->customColor === null ? null : (string)$this->customColor,
			'showLabels' => $this->showLabels,
			'keepAppMenu' => $this->keepAppMenu,
			'hiddenApps' => $this->hiddenApps->toList(),
			'collapsedApps' => $this->collapsedApps->toList(),
			'pinnedApps' => $this->pinnedApps->toList(),
			'expanded' => $this->expanded,
			'moreOpen' => $this->moreOpen,
			'railHidden' => $this->railHidden,
		];
	}
}
