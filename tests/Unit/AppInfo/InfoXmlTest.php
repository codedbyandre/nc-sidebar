<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 André Wiesehoff
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Sidebar\Tests\Unit\AppInfo;

use PHPUnit\Framework\TestCase;

final class InfoXmlTest extends TestCase {
	private const ROOT = __DIR__ . '/../../..';

	public function testManifestMatchesApp(): void {
		$info = simplexml_load_file(self::ROOT . '/appinfo/info.xml');
		self::assertNotFalse($info);
		self::assertSame('sidebar', (string)$info->id);
		self::assertSame('Sidebar', (string)$info->namespace);
		self::assertSame(0, count($info->navigations));
	}

	/**
	 * The release workflow refuses a tag whose versions differ.
	 */
	public function testManifestAndPackageShareTheVersion(): void {
		$info = simplexml_load_file(self::ROOT . '/appinfo/info.xml');
		$package = json_decode((string)file_get_contents(self::ROOT . '/package.json'), true, flags: JSON_THROW_ON_ERROR);
		self::assertNotFalse($info);
		self::assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', (string)$info->version);
		self::assertSame((string)$info->version, $package['version']);
	}
}
