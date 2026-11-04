<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Replicate\Service;

use OCA\Replicate\AppInfo\Application;
use OCA\Replicate\AuditLog\AuditEventFactory;
use OCA\Replicate\AuditLog\Operation;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Exceptions\AppConfigTypeConflictException;
use OCP\IAppConfig;

class ConfigService {
	/**
	 * Settings whose values are model identifiers and can be logged.
	 * The extra params are free-form JSON that may contain anything, so only their key is logged.
	 */
	private const KEYS_WITH_LOGGED_VALUE = [
		'model',
		'llm_model_name',
		'llm_model_version',
		'igen_model_name',
		'igen_model_version',
	];

	public function __construct(
		private IAppConfig $appConfig,
		private IEventDispatcher $eventDispatcher,
	) {
	}

	/**
	 * @throws AppConfigTypeConflictException
	 */
	public function setAdminValue(string $key, string $value): void {
		$changed = $this->appConfig->setValueString(Application::APP_ID, $key, $value, lazy: true);
		if (!$changed) {
			return;
		}
		if (in_array($key, self::KEYS_WITH_LOGGED_VALUE, true)) {
			$event = AuditEventFactory::create(
				Operation::AdminConfigUpdated,
				'Replicate integration setting "%s" was changed to "%s"',
				['key' => $key, 'value' => $value],
			);
		} else {
			$event = AuditEventFactory::create(
				Operation::AdminConfigUpdated,
				'Replicate integration setting "%s" was changed',
				['key' => $key],
			);
		}
		$this->eventDispatcher->dispatchTyped($event);
	}

	/**
	 * Never logs the key itself
	 *
	 * @throws AppConfigTypeConflictException
	 */
	public function setApiKey(string $apiKey): void {
		$changed = $this->appConfig->setValueString(Application::APP_ID, 'api_key', $apiKey, lazy: false, sensitive: true);
		if (!$changed) {
			return;
		}
		$apiKeySet = $apiKey !== '';
		$this->eventDispatcher->dispatchTyped(AuditEventFactory::create(
			Operation::ApiKeyUpdated,
			$apiKeySet ? 'Replicate API key was changed' : 'Replicate API key was removed',
			['apiKeySet' => $apiKeySet],
		));
	}
}
