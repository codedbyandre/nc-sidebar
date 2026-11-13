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
	/** Hides the app menu in the header. Kept in step with the rail's stylesheet. */
	private const HIDE_APP_MENU_CSS = '#header-start__appmenu,#header .app-menu{display:none}';

	public function __construct(
		public RailTheme $theme,
		public bool $showLabels,
		public bool $expanded,
		public bool $showLogo,
		public bool $showMoreApps,
		public AppIdList $hiddenApps,
		public AppIdList $collapsedApps,
		public bool $moreOpen,
		public bool $railHidden,
		public bool $keepAppMenu,
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
		$keepAppMenu = $admin->allowUserLayout && $user->keepAppMenu !== null
			? $user->keepAppMenu
			: $admin->keepAppMenu;
		$hiddenApps = $admin->hiddenApps;
		$collapsedApps = $admin->collapsedApps;
		if ($admin->allowUserHiddenApps) {
			$hiddenApps = $hiddenApps->merge($user->hiddenApps);
			$collapsedApps = $collapsedApps->without($user->pinnedApps)->merge($user->collapsedApps);
		}

		return new self(
			theme: RailTheme::for($colorMode, $customColor),
			showLabels: $showLabels,
			expanded: $user->expanded ?? $admin->expandedByDefault,
			showLogo: $admin->showLogo,
			showMoreApps: $admin->showMoreApps,
			hiddenApps: $hiddenApps,
			collapsedApps: $collapsedApps->without($hiddenApps),
			moreOpen: $user->moreOpen ?? false,
			railHidden: $user->railHidden ?? false,
			keepAppMenu: $keepAppMenu,
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
	 * rail's width and colors before any script runs. A hidden rail takes no
	 * width on the desktop; the drawer on small screens ignores the width.
	 */
	public function css(): string {
		$declarations = ['--nc-sidebar-width:' . ($this->railHidden ? '0px' : $this->widthVariable())];
		foreach ($this->theme->variables() as $name => $value) {
			$declarations[] = $name . ':' . $value;
		}

		return 'body{' . implode(';', $declarations) . '}';
	}

	/**
	 * Inline style that hides the header's app menu on the first paint, or
	 * null when the menu stays. The rail's script takes over once it runs.
	 */
	public function appMenuCss(): ?string {
		return $this->keepAppMenu ? null : self::HIDE_APP_MENU_CSS;
	}

	/**
	 * @return array{theme: array{mode: string, variables: array<string, string>}, showLabels: bool, expanded: bool, showLogo: bool, showMoreApps: bool, hiddenApps: list<string>, collapsedApps: list<string>, moreOpen: bool, railHidden: bool, keepAppMenu: bool}
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
			'collapsedApps' => $this->collapsedApps->toList(),
			'moreOpen' => $this->moreOpen,
			'railHidden' => $this->railHidden,
			'keepAppMenu' => $this->keepAppMenu,
		];
	}
}
