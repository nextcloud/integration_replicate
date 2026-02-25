<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Replicate\Migration;

use Closure;
use OCP\AppFramework\Services\IAppConfig;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version050002Date20260225105515 extends SimpleMigrationStep {

	public function __construct(
		private IAppConfig $appConfig,
	) {
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
		// Make app config entries lazy
		foreach ($this->appConfig->getAppKeys() as $key) {
			if (in_array($key, ['installed_version','enabled','types', 'api_key'], true)) {
				continue;
			}
			if (!$this->appConfig->isLazy($key)) {
				$value = $this->appConfig->getAppValueString($key);
				$this->appConfig->deleteAppValue($key);
				$this->appConfig->setAppValueString($key, $value, lazy: true);
			}
		}
	}
}
