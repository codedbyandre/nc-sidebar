<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 André Wiesehoff
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Sidebar\Controller;

use OCA\Sidebar\Model\InvalidSettings;
use OCA\Sidebar\Service\AdminSettingsStore;
use OCA\Sidebar\Service\RailConfigService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;

/**
 * Administrator only: no route here carries NoAdminRequired.
 */
final class AdminSettingsController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly AdminSettingsStore $store,
		private readonly RailConfigService $railConfig,
		private readonly ?string $userId,
	) {
		parent::__construct($appName, $request);
	}

	#[ApiRoute(verb: 'GET', url: '/api/v1/admin/settings')]
	public function show(): DataResponse {
		return new DataResponse(['settings' => $this->store->current()]);
	}

	/**
	 * @param mixed $settings Partial settings document
	 */
	#[ApiRoute(verb: 'PATCH', url: '/api/v1/admin/settings')]
	public function update(mixed $settings = null): DataResponse {
		if (!is_array($settings)) {
			return $this->invalid([['field' => 'settings', 'code' => 'type_object', 'message' => 'Expected an object']]);
		}
		try {
			$saved = $this->store->update($settings);
		} catch (InvalidSettings $e) {
			return $this->invalid($e->errors);
		}

		return $this->saved($saved);
	}

	#[ApiRoute(verb: 'DELETE', url: '/api/v1/admin/settings')]
	public function reset(): DataResponse {
		return $this->saved($this->store->reset());
	}

	private function saved(\JsonSerializable $settings): DataResponse {
		return new DataResponse([
			'settings' => $settings,
			'rail' => $this->userId === null ? null : $this->railConfig->forUser($this->userId),
		]);
	}

	/**
	 * @param list<array{field: string, code: string, message: string}> $errors
	 */
	private function invalid(array $errors): DataResponse {
		return new DataResponse(['errors' => $errors], Http::STATUS_BAD_REQUEST);
	}
}
