<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 André Wiesehoff
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Sidebar\Tests\Unit\Model;

use OCA\Sidebar\Model\AdminSettings;
use OCA\Sidebar\Model\InvalidSettings;
use OCA\Sidebar\Model\RailConfig;
use OCA\Sidebar\Model\UserPreferences;
use PHPUnit\Framework\TestCase;

final class RailConfigTest extends TestCase {
	public function testDefaultsUseTheThemeColorAndTheCompactWidth(): void {
		$config = RailConfig::resolve(AdminSettings::defaults(), UserPreferences::defaults());

		self::assertSame('theme', $config->theme->mode->value);
		self::assertSame(
			'body{--nc-sidebar-width:var(--nc-sidebar-width-compact);--nc-sidebar-background:var(--color-primary);--nc-sidebar-text:var(--color-primary-text);--nc-sidebar-border:transparent}',
			$config->css(),
		);
	}

	public function testUserOverridesApplyWhenAllowed(): void {
		$admin = AdminSettings::defaults()->with(['hiddenApps' => ['activity']]);
		$user = UserPreferences::defaults()->with([
			'colorMode' => 'custom',
			'customColor' => '#ffd84d',
			'showLabels' => true,
			'hiddenApps' => ['photos'],
			'expanded' => true,
		], $admin);

		$config = RailConfig::resolve($admin, $user);

		self::assertSame('custom', $config->theme->mode->value);
		self::assertSame('#000000', $config->theme->variables()['--nc-sidebar-text']);
		self::assertTrue($config->showLabels);
		self::assertTrue($config->expanded);
		self::assertSame(['activity', 'photos'], $config->hiddenApps->toList());
		self::assertSame('var(--nc-sidebar-width-expanded)', $config->widthVariable());
	}

	public function testLockedFieldsFallBackToTheAdministrator(): void {
		$open = AdminSettings::defaults();
		$user = UserPreferences::defaults()->with([
			'colorMode' => 'neutral',
			'showLabels' => true,
			'hiddenApps' => ['photos'],
		], $open);
		$locked = $open->with([
			'colorMode' => 'custom',
			'customColor' => '#1a1a2e',
			'allowUserColor' => false,
			'allowUserLayout' => false,
			'allowUserHiddenApps' => false,
		]);

		$config = RailConfig::resolve($locked, $user);

		self::assertSame('#1a1a2e', $config->theme->variables()['--nc-sidebar-background']);
		self::assertFalse($config->showLabels);
		self::assertSame([], $config->hiddenApps->toList());
		self::assertSame('var(--nc-sidebar-width-compact)', $config->widthVariable());
	}

	public function testLockedFieldsCannotBeChanged(): void {
		$policy = AdminSettings::defaults()->with(['allowUserColor' => false]);

		try {
			UserPreferences::defaults()->with(['colorMode' => 'neutral', 'expanded' => true], $policy);
			self::fail('Expected InvalidSettings');
		} catch (InvalidSettings $e) {
			self::assertSame('colorMode', $e->errors[0]['field']);
			self::assertSame('locked', $e->errors[0]['code']);
		}

		$expanded = UserPreferences::defaults()->with(['expanded' => true], $policy);
		self::assertTrue($expanded->expanded);
	}

	public function testNullRestoresTheDefault(): void {
		$policy = AdminSettings::defaults();
		$user = UserPreferences::defaults()
			->with(['showLabels' => true, 'colorMode' => 'neutral'], $policy)
			->with(['showLabels' => null, 'colorMode' => null], $policy);

		self::assertNull($user->showLabels);
		self::assertNull($user->colorMode);
	}

	public function testAppsUnderMoreFollowTheAdministratorByDefault(): void {
		$admin = AdminSettings::defaults()->with(['collapsedApps' => ['notes', 'deck'], 'hiddenApps' => ['deck']]);

		$config = RailConfig::resolve($admin, UserPreferences::defaults());

		self::assertSame(['notes'], $config->collapsedApps->toList());
		self::assertSame(['deck'], $config->hiddenApps->toList());
		self::assertFalse($config->moreOpen);
	}

	public function testPeopleMayRearrangeAppsFreely(): void {
		$admin = AdminSettings::defaults()->with(['collapsedApps' => ['notes', 'deck', 'tables']]);
		$user = UserPreferences::defaults()->with([
			'pinnedApps' => ['notes'],
			'hiddenApps' => ['deck'],
			'collapsedApps' => ['photos'],
			'moreOpen' => true,
		], $admin);

		$config = RailConfig::resolve($admin, $user);

		self::assertSame(['tables', 'photos'], $config->collapsedApps->toList());
		self::assertSame(['deck'], $config->hiddenApps->toList());
		self::assertTrue($config->moreOpen);
	}

	public function testPinningDoesNotBringBackAnAppTheAdministratorHid(): void {
		$admin = AdminSettings::defaults()->with(['hiddenApps' => ['activity']]);
		$user = UserPreferences::defaults()->with(['pinnedApps' => ['activity'], 'collapsedApps' => ['activity']], $admin);

		$config = RailConfig::resolve($admin, $user);

		self::assertSame(['activity'], $config->hiddenApps->toList());
		self::assertSame([], $config->collapsedApps->toList());
	}

	public function testLockedArrangementIgnoresStoredPersonalLists(): void {
		$open = AdminSettings::defaults()->with(['collapsedApps' => ['notes']]);
		$user = UserPreferences::defaults()->with(['pinnedApps' => ['notes'], 'collapsedApps' => ['photos']], $open);

		$config = RailConfig::resolve($open->with(['allowUserHiddenApps' => false]), $user);

		self::assertSame(['notes'], $config->collapsedApps->toList());
	}

	public function testArrangementListsAreLockedTogether(): void {
		$policy = AdminSettings::defaults()->with(['allowUserHiddenApps' => false]);

		foreach (['hiddenApps', 'collapsedApps', 'pinnedApps'] as $field) {
			try {
				UserPreferences::defaults()->with([$field => ['photos']], $policy);
				self::fail('Expected InvalidSettings for ' . $field);
			} catch (InvalidSettings $e) {
				self::assertSame(['field' => $field, 'code' => 'locked'], ['field' => $e->errors[0]['field'], 'code' => $e->errors[0]['code']]);
			}
		}

		$viewState = UserPreferences::defaults()->with(['moreOpen' => true, 'railHidden' => true], $policy);
		self::assertTrue($viewState->moreOpen);
		self::assertTrue($viewState->railHidden);
	}

	public function testPreferencesStoredBeforeMoreExistedStillLoad(): void {
		$user = UserPreferences::defaults()->with([
			'colorMode' => null,
			'customColor' => null,
			'showLabels' => true,
			'hiddenApps' => ['photos'],
			'expanded' => null,
		]);

		self::assertSame(['photos'], $user->hiddenApps->toList());
		self::assertSame([], $user->collapsedApps->toList());
		self::assertNull($user->railHidden);
	}

	public function testAHiddenRailTakesNoWidthOnTheFirstPaint(): void {
		$user = UserPreferences::defaults()->with(['railHidden' => true, 'expanded' => true]);

		$config = RailConfig::resolve(AdminSettings::defaults(), $user);

		self::assertTrue($config->railHidden);
		self::assertStringStartsWith('body{--nc-sidebar-width:0px;', $config->css());
		self::assertSame('var(--nc-sidebar-width-expanded)', $config->widthVariable());
	}

	public function testTheAppMenuIsHiddenUnlessKept(): void {
		$admin = AdminSettings::defaults();
		self::assertSame(
			'#header-start__appmenu,#header .app-menu{display:none}',
			RailConfig::resolve($admin, UserPreferences::defaults())->appMenuCss(),
		);

		$kept = RailConfig::resolve($admin->with(['keepAppMenu' => true]), UserPreferences::defaults());
		self::assertTrue($kept->keepAppMenu);
		self::assertNull($kept->appMenuCss());
	}

	public function testPeopleMayKeepTheAppMenuWhenLayoutIsOpen(): void {
		$open = AdminSettings::defaults();
		$user = UserPreferences::defaults()->with(['keepAppMenu' => true], $open);

		self::assertTrue(RailConfig::resolve($open, $user)->keepAppMenu);
		self::assertFalse(RailConfig::resolve($open->with(['allowUserLayout' => false]), $user)->keepAppMenu);

		$this->expectException(InvalidSettings::class);
		UserPreferences::defaults()->with(['keepAppMenu' => true], $open->with(['allowUserLayout' => false]));
	}
}
