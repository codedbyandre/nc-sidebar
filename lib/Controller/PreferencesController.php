<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 André Wiesehoff
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Sidebar\Controller;

use OCA\Sidebar\Model\InvalidSettings;
use OCA\Sidebar\Model\UserPreferences;
use OCA\Sidebar\Service\AdminSettingsStore;
use OCA\Sidebar\Service\RailConfigService;
use OCA\Sidebar\Service\UserPreferencesStore;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;

final class PreferencesController extends OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private readonly UserPreferencesStore $preferences,
		private readonly AdminSettingsStore $adminSettings,
		private readonly RailConfigService $railConfig,
		private readonly ?string $userId,
	) {
		parent::__construct($appName, $request);
	}

	#[ApiRoute(verb: 'GET', url: '/api/v1/preferences')]
	#[NoAdminRequired]
	public function show(): DataResponse {
		if ($this->userId === null) {
			return new DataResponse([], Http::STATUS_UNAUTHORIZED);
		}

		return $this->respond($this->userId, $this->preferences->current($this->userId));
	}

	/**
	 * @param mixed $preferences Partial preferences document
	 */
	#[ApiRoute(verb: 'PATCH', url: '/api/v1/preferences')]
	#[NoAdminRequired]
	public function update(mixed $preferences = null): DataResponse {
		if ($this->userId === null) {
			return new DataResponse([], Http::STATUS_UNAUTHORIZED);
		}
		if (!is_array($preferences)) {
			return new DataResponse(
				['errors' => [['field' => 'preferences', 'code' => 'type_object', 'message' => 'Expected an object']]],
				Http::STATUS_BAD_REQUEST,
			);
		}
		try {
			$saved = $this->preferences->update($this->userId, $preferences, $this->adminSettings->current());
		} catch (InvalidSettings $e) {
			$locked = in_array('locked', array_column($e->errors, 'code'), true);
			return new DataResponse(
				['errors' => $e->errors],
				$locked ? Http::STATUS_FORBIDDEN : Http::STATUS_BAD_REQUEST,
			);
		}

		return $this->respond($this->userId, $saved);
	}

	#[ApiRoute(verb: 'DELETE', url: '/api/v1/preferences')]
	#[NoAdminRequired]
	public function reset(): DataResponse {
		if ($this->userId === null) {
			return new DataResponse([], Http::STATUS_UNAUTHORIZED);
		}

		return $this->respond($this->userId, $this->preferences->reset($this->userId));
	}

	private function respond(string $userId, UserPreferences $preferences): DataResponse {
		return new DataResponse([
			'preferences' => $preferences,
			'defaults' => $this->adminSettings->current(),
			'rail' => $this->railConfig->forUser($userId),
		]);
	}
}
