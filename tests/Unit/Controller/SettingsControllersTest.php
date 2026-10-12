<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 André Wiesehoff
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Sidebar\Tests\Unit\Controller;

use OCA\Sidebar\Controller\AdminSettingsController;
use OCA\Sidebar\Controller\PreferencesController;
use OCA\Sidebar\Service\AdminSettingsStore;
use OCA\Sidebar\Service\RailConfigService;
use OCA\Sidebar\Service\UserPreferencesStore;
use OCP\AppFramework\Http;
use OCP\Config\IUserConfig;
use OCP\IAppConfig;
use OCP\IRequest;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class SettingsControllersTest extends TestCase {
	/** @var array<string, array<array-key, mixed>> */
	private array $appValues = [];
	/** @var array<string, array<array-key, mixed>> */
	private array $userValues = [];
	private AdminSettingsController $admin;
	private PreferencesController $preferences;

	protected function setUp(): void {
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getValueArray')->willReturnCallback(
			fn (string $app, string $key): array => $this->appValues[$key] ?? [],
		);
		$appConfig->method('setValueArray')->willReturnCallback(
			function (string $app, string $key, array $value): bool {
				$this->appValues[$key] = $value;
				return true;
			},
		);
		$appConfig->method('deleteKey')->willReturnCallback(
			function (string $app, string $key): void {
				unset($this->appValues[$key]);
			},
		);

		$userConfig = $this->createMock(IUserConfig::class);
		$userConfig->method('getValueArray')->willReturnCallback(
			fn (string $userId, string $app, string $key): array => $this->userValues[$userId . '/' . $key] ?? [],
		);
		$userConfig->method('setValueArray')->willReturnCallback(
			function (string $userId, string $app, string $key, array $value): bool {
				$this->userValues[$userId . '/' . $key] = $value;
				return true;
			},
		);
		$userConfig->method('deleteUserConfig')->willReturnCallback(
			function (string $userId, string $app, string $key): void {
				unset($this->userValues[$userId . '/' . $key]);
			},
		);

		$adminStore = new AdminSettingsStore($appConfig, new NullLogger());
		$userStore = new UserPreferencesStore($userConfig, new NullLogger());
		$rail = new RailConfigService($adminStore, $userStore);
		$request = $this->createStub(IRequest::class);

		$this->admin = new AdminSettingsController('sidebar', $request, $adminStore, $rail, 'alice');
		$this->preferences = new PreferencesController('sidebar', $request, $userStore, $adminStore, $rail, 'alice');
	}

	public function testAdminSaveReturnsSettingsAndTheResolvedRail(): void {
		$response = $this->admin->update(['colorMode' => 'custom', 'customColor' => '#1A1A2E']);

		self::assertSame(Http::STATUS_OK, $response->getStatus());
		$data = json_decode(json_encode($response->getData(), JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
		self::assertSame('#1a1a2e', $data['settings']['customColor']);
		self::assertSame('#1a1a2e', $data['rail']['theme']['variables']['--nc-sidebar-background']);
		self::assertSame('#1a1a2e', $this->appValues['settings']['customColor']);
	}

	public function testAdminInvalidInputIs400AndStoresNothing(): void {
		$response = $this->admin->update(['customColor' => 'javascript:alert(1)']);

		self::assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
		self::assertSame('customColor', $response->getData()['errors'][0]['field']);
		self::assertSame([], $this->appValues);
	}

	public function testAdminBodyMustBeAnObject(): void {
		self::assertSame(Http::STATUS_BAD_REQUEST, $this->admin->update('colorMode=neutral')->getStatus());
	}

	public function testAdminResetRestoresDefaults(): void {
		$this->admin->update(['showLabels' => true]);

		$response = $this->admin->reset();

		self::assertFalse($response->getData()['settings']->showLabels);
		self::assertArrayNotHasKey('settings', $this->appValues);
	}

	public function testPreferencesSaveOnlyWhatChanged(): void {
		$this->preferences->update(['showLabels' => true]);
		$response = $this->preferences->update(['expanded' => true]);

		self::assertSame(Http::STATUS_OK, $response->getStatus());
		self::assertTrue($this->userValues['alice/preferences']['showLabels']);
		self::assertTrue($this->userValues['alice/preferences']['expanded']);
	}

	public function testLockedPreferenceIs403(): void {
		$this->admin->update(['allowUserHiddenApps' => false]);

		$response = $this->preferences->update(['hiddenApps' => ['photos']]);

		self::assertSame(Http::STATUS_FORBIDDEN, $response->getStatus());
		self::assertSame('locked', $response->getData()['errors'][0]['code']);
		self::assertArrayNotHasKey('alice/preferences', $this->userValues);
	}

	public function testPreferencesResetDropsOverrides(): void {
		$this->preferences->update(['colorMode' => 'neutral']);

		$response = $this->preferences->reset();

		self::assertSame(Http::STATUS_OK, $response->getStatus());
		self::assertArrayNotHasKey('alice/preferences', $this->userValues);
		self::assertSame('theme', $response->getData()['rail']->theme->mode->value);
	}

	public function testCorruptStoredPreferencesFallBackToDefaults(): void {
		$this->userValues['alice/preferences'] = ['colorMode' => 'custom', 'customColor' => '"><script>'];

		$response = $this->preferences->show();

		self::assertSame(Http::STATUS_OK, $response->getStatus());
		self::assertNull($response->getData()['preferences']->colorMode);
	}
}
