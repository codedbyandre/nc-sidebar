<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 André Wiesehoff
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Sidebar\Model;

final readonly class AppIdList implements \JsonSerializable {
	public const MAX_ENTRIES = 200;
	private const PATTERN = '/^[A-Za-z0-9_.-]{1,64}$/';

	/**
	 * @param list<string> $ids
	 */
	private function __construct(
		private array $ids,
	) {
	}

	public static function empty(): self {
		return new self([]);
	}

	/**
	 * Navigation entry ids, deduplicated in their given order.
	 *
	 * @throws \InvalidArgumentException
	 */
	public static function parse(mixed $raw): self {
		if (!is_array($raw) || !array_is_list($raw)) {
			throw new \InvalidArgumentException('Expected a list of app ids');
		}
		if (count($raw) > self::MAX_ENTRIES) {
			throw new \InvalidArgumentException('At most ' . self::MAX_ENTRIES . ' app ids are allowed');
		}
		$ids = [];
		foreach ($raw as $id) {
			if (!is_string($id) || preg_match(self::PATTERN, $id) !== 1) {
				throw new \InvalidArgumentException('App ids contain only letters, digits, dot, dash and underscore');
			}
			$ids[$id] = true;
		}

		return new self(array_keys($ids));
	}

	public function merge(self $other): self {
		return new self(array_values(array_unique([...$this->ids, ...$other->ids])));
	}

	/**
	 * @return list<string>
	 */
	public function toList(): array {
		return $this->ids;
	}

	/**
	 * @return list<string>
	 */
	#[\Override]
	public function jsonSerialize(): array {
		return $this->ids;
	}
}
