<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 André Wiesehoff
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Sidebar\Tests\Unit\AppInfo;

use PHPUnit\Framework\TestCase;

final class InfoXmlTest extends TestCase {
	public function testManifestMatchesApp(): void {
		$info = simplexml_load_file(dirname(__DIR__, 3) . '/appinfo/info.xml');
		self::assertNotFalse($info);
		self::assertSame('sidebar', (string)$info->id);
		self::assertSame('Sidebar', (string)$info->namespace);
		self::assertSame('0.2.0', (string)$info->version);
		self::assertSame(0, count($info->navigations));
	}
}
