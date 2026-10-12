<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 André Wiesehoff
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Sidebar\Model;

/**
 * One person's overrides. Null means "use the administrator's default".
 */
final readonly class UserPreferences implements \JsonSerializable {
	private const KEYS = [
		'colorMode',
		'customColor',
		'showLabels',
		'hiddenApps',
		'expanded',
	];

	public function __construct(
		public ?ColorMode $colorMode,
		public ?HexColor $customColor,
		public ?bool $showLabels,
		public AppIdList $hiddenApps,
		public ?bool $expanded,
	) {
	}

	public static function defaults(): self {
		return new self(null, null, null, AppIdList::empty(), null);
	}

	/**
	 * Applies a partial document. With a policy, fields the administrator
	 * locked are rejected; the expanded state is view state and never locked.
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
			'hiddenApps' => !$policy->allowUserHiddenApps,
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
			hiddenApps: $input->appIds('hiddenApps', $this->hiddenApps),
			expanded: $input->nullableBool('expanded', $this->expanded),
		);
		if ($next->colorMode === ColorMode::Custom && $next->customColor === null && !$input->hasError('customColor')) {
			$input->fail('customColor', 'required', 'A custom color needs a hex value');
		}
		$input->throwIfInvalid();

		return $next;
	}

	/**
	 * @return array{colorMode: ?string, customColor: ?string, showLabels: ?bool, hiddenApps: list<string>, expanded: ?bool}
	 */
	#[\Override]
	public function jsonSerialize(): array {
		return [
			'colorMode' => $this->colorMode?->value,
			'customColor' => $this->customColor === null ? null : (string)$this->customColor,
			'showLabels' => $this->showLabels,
			'hiddenApps' => $this->hiddenApps->toList(),
			'expanded' => $this->expanded,
		];
	}
}
