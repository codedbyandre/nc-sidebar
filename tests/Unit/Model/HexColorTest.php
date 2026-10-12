<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 André Wiesehoff
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Sidebar\Tests\Unit\Model;

use OCA\Sidebar\Model\HexColor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class HexColorTest extends TestCase {
	public function testNormalizesCaseAndShortForm(): void {
		self::assertSame('#0082c9', (string)HexColor::parse(' #0082C9 '));
		self::assertSame('#aabbcc', (string)HexColor::parse('#ABC'));
	}

	/**
	 * @return iterable<string, array{string}>
	 */
	public static function invalidColors(): iterable {
		yield 'no hash' => ['0082c9'];
		yield 'named color' => ['red'];
		yield 'alpha channel' => ['#0082c9ff'];
		yield 'css injection' => ['#000;}body{display:none'];
		yield 'empty' => [''];
		yield 'non-hex digit' => ['#00gg00'];
	}

	#[DataProvider('invalidColors')]
	public function testRejectsAnythingButHex(string $raw): void {
		$this->expectException(\InvalidArgumentException::class);
		HexColor::parse($raw);
	}

	public function testReadableTextOnDarkAndLightBackgrounds(): void {
		self::assertSame('#ffffff', (string)HexColor::parse('#1a1a2e')->readableText());
		self::assertSame('#000000', (string)HexColor::parse('#ffd84d')->readableText());
	}

	public function testChosenTextAlwaysMeetsWcagAa(): void {
		foreach (['#777777', '#808080', '#0082c9', '#ff0000', '#00ff00', '#7f7f00'] as $raw) {
			$color = HexColor::parse($raw);
			self::assertGreaterThanOrEqual(4.5, $color->contrastWith($color->readableText()), $raw);
		}
	}
}
