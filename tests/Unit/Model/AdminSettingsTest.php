<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 André Wiesehoff
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Sidebar\Tests\Unit\Model;

use OCA\Sidebar\Model\AdminSettings;
use OCA\Sidebar\Model\ColorMode;
use OCA\Sidebar\Model\InvalidSettings;
use PHPUnit\Framework\TestCase;

final class AdminSettingsTest extends TestCase {
	public function testPartialChangesKeepOtherValues(): void {
		$settings = AdminSettings::defaults()->with(['showLabels' => true]);

		self::assertTrue($settings->showLabels);
		self::assertSame(ColorMode::Theme, $settings->colorMode);
		self::assertTrue($settings->showLogo);
	}

	public function testCustomColorIsNormalized(): void {
		$settings = AdminSettings::defaults()->with(['colorMode' => 'custom', 'customColor' => '#ABC']);

		self::assertSame('custom', $settings->jsonSerialize()['colorMode']);
		self::assertSame('#aabbcc', $settings->jsonSerialize()['customColor']);
	}

	public function testCustomModeNeedsAColor(): void {
		$errors = $this->errorsOf(['colorMode' => 'custom']);

		self::assertSame([['field' => 'customColor', 'code' => 'required']], $errors);
	}

	public function testCollectsEveryProblemAtOnce(): void {
		$errors = $this->errorsOf([
			'colorMode' => 'rainbow',
			'customColor' => 'red',
			'showLabels' => 'yes',
			'hiddenApps' => ['files', '../etc'],
			'surprise' => true,
		]);

		self::assertSame([
			['field' => 'surprise', 'code' => 'unknown'],
			['field' => 'colorMode', 'code' => 'enum'],
			['field' => 'customColor', 'code' => 'color'],
			['field' => 'showLabels', 'code' => 'type_bool'],
			['field' => 'hiddenApps', 'code' => 'app_ids'],
		], $errors);
	}

	public function testHiddenAppsAreDeduplicated(): void {
		$settings = AdminSettings::defaults()->with(['hiddenApps' => ['photos', 'files', 'photos']]);

		self::assertSame(['photos', 'files'], $settings->hiddenApps->toList());
	}

	public function testHiddenAppsMustBeAList(): void {
		self::assertSame(
			[['field' => 'hiddenApps', 'code' => 'app_ids']],
			$this->errorsOf(['hiddenApps' => ['a' => 'files']]),
		);
	}

	public function testRoundTripsThroughJson(): void {
		$settings = AdminSettings::defaults()->with([
			'colorMode' => 'neutral',
			'expandedByDefault' => true,
			'hiddenApps' => ['activity'],
			'allowUserColor' => false,
		]);

		self::assertEquals($settings, AdminSettings::defaults()->with($settings->jsonSerialize()));
	}

	/**
	 * @param array<string, mixed> $changes
	 * @return list<array{field: string, code: string}>
	 */
	private function errorsOf(array $changes): array {
		try {
			AdminSettings::defaults()->with($changes);
		} catch (InvalidSettings $e) {
			return array_map(
				static fn (array $error): array => ['field' => $error['field'], 'code' => $error['code']],
				$e->errors,
			);
		}
		self::fail('Expected InvalidSettings');
	}
}
