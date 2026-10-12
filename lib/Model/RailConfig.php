<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 André Wiesehoff
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Sidebar\Model;

/**
 * What the rail renders for one person: the administrator's settings with
 * that person's permitted overrides applied.
 */
final readonly class RailConfig implements \JsonSerializable {
	public function __construct(
		public RailTheme $theme,
		public bool $showLabels,
		public bool $expanded,
		public bool $showLogo,
		public bool $showMoreApps,
		public AppIdList $hiddenApps,
	) {
	}

	public static function resolve(AdminSettings $admin, UserPreferences $user): self {
		$colorMode = $admin->colorMode;
		$customColor = $admin->customColor;
		if ($admin->allowUserColor && $user->colorMode !== null) {
			$colorMode = $user->colorMode;
			$customColor = $user->customColor;
		}
		$showLabels = $admin->allowUserLayout && $user->showLabels !== null
			? $user->showLabels
			: $admin->showLabels;
		$hiddenApps = $admin->allowUserHiddenApps
			? $admin->hiddenApps->merge($user->hiddenApps)
			: $admin->hiddenApps;

		return new self(
			theme: RailTheme::for($colorMode, $customColor),
			showLabels: $showLabels,
			expanded: $user->expanded ?? $admin->expandedByDefault,
			showLogo: $admin->showLogo,
			showMoreApps: $admin->showMoreApps,
			hiddenApps: $hiddenApps,
		);
	}

	public function widthVariable(): string {
		if ($this->expanded) {
			return 'var(--nc-sidebar-width-expanded)';
		}

		return $this->showLabels ? 'var(--nc-sidebar-width-labeled)' : 'var(--nc-sidebar-width-compact)';
	}

	/**
	 * Inline style for the page head, so the first paint already has the
	 * rail's width and colors before any script runs.
	 */
	public function css(): string {
		$declarations = ['--nc-sidebar-width:' . $this->widthVariable()];
		foreach ($this->theme->variables() as $name => $value) {
			$declarations[] = $name . ':' . $value;
		}

		return 'body{' . implode(';', $declarations) . '}';
	}

	/**
	 * @return array{theme: array{mode: string, variables: array<string, string>}, showLabels: bool, expanded: bool, showLogo: bool, showMoreApps: bool, hiddenApps: list<string>}
	 */
	#[\Override]
	public function jsonSerialize(): array {
		return [
			'theme' => $this->theme->jsonSerialize(),
			'showLabels' => $this->showLabels,
			'expanded' => $this->expanded,
			'showLogo' => $this->showLogo,
			'showMoreApps' => $this->showMoreApps,
			'hiddenApps' => $this->hiddenApps->toList(),
		];
	}
}
