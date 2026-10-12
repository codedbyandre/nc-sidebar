<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 André Wiesehoff
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Sidebar\Model;

/**
 * Reads a partial settings document field by field and collects every
 * problem, so a client sees all errors of one request at once.
 */
final class SettingsInput {
	/** @var list<array{field: string, code: string, message: string}> */
	private array $errors = [];

	/**
	 * @param array<array-key, mixed> $input
	 * @param list<string> $allowedKeys
	 */
	public function __construct(
		private readonly array $input,
		array $allowedKeys,
	) {
		foreach (array_keys($input) as $key) {
			if (!in_array($key, $allowedKeys, true)) {
				$this->fail((string)$key, 'unknown', 'Unknown setting');
			}
		}
	}

	public function bool(string $key, bool $current): bool {
		return $this->readBool($key, $current, false) ?? $current;
	}

	public function nullableBool(string $key, ?bool $current): ?bool {
		return $this->readBool($key, $current, true);
	}

	private function readBool(string $key, ?bool $current, bool $nullable): ?bool {
		if (!array_key_exists($key, $this->input)) {
			return $current;
		}
		$value = $this->input[$key];
		if ($value === null && $nullable) {
			return null;
		}
		if (!is_bool($value)) {
			$this->fail($key, 'type_bool', 'Expected true or false');
			return $current;
		}

		return $value;
	}

	public function colorMode(string $key, ColorMode $current): ColorMode {
		return $this->nullableColorMode($key, $current, false) ?? $current;
	}

	public function nullableColorMode(string $key, ?ColorMode $current, bool $nullable = true): ?ColorMode {
		if (!array_key_exists($key, $this->input)) {
			return $current;
		}
		$value = $this->input[$key];
		if ($value === null && $nullable) {
			return null;
		}
		$mode = is_string($value) ? ColorMode::tryFrom($value) : null;
		if ($mode === null) {
			$this->fail($key, 'enum', 'Expected one of ' . implode(', ', ColorMode::values()));
			return $current;
		}

		return $mode;
	}

	public function color(string $key, ?HexColor $current): ?HexColor {
		if (!array_key_exists($key, $this->input)) {
			return $current;
		}
		$value = $this->input[$key];
		if ($value === null) {
			return null;
		}
		if (!is_string($value)) {
			$this->fail($key, 'color', 'Expected a hex color like #0082c9');
			return $current;
		}
		try {
			return HexColor::parse($value);
		} catch (\InvalidArgumentException $e) {
			$this->fail($key, 'color', $e->getMessage());
			return $current;
		}
	}

	public function appIds(string $key, AppIdList $current): AppIdList {
		if (!array_key_exists($key, $this->input)) {
			return $current;
		}
		try {
			return AppIdList::parse($this->input[$key]);
		} catch (\InvalidArgumentException $e) {
			$this->fail($key, 'app_ids', $e->getMessage());
			return $current;
		}
	}

	public function hasError(string $field): bool {
		return in_array($field, array_column($this->errors, 'field'), true);
	}

	public function fail(string $field, string $code, string $message): void {
		$this->errors[] = ['field' => $field, 'code' => $code, 'message' => $message];
	}

	/**
	 * @throws InvalidSettings
	 */
	public function throwIfInvalid(): void {
		if ($this->errors !== []) {
			throw new InvalidSettings($this->errors);
		}
	}
}
