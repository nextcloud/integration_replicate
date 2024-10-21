<?php
/**
 * Nextcloud - Replicate
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Julien Veyssier <julien-nc@posteo.net>
 * @copyright Julien Veyssier 2022
 */

namespace OCA\Replicate\Controller;

use OCA\Replicate\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\Exceptions\AppConfigTypeConflictException;
use OCP\IAppConfig;

use OCP\IRequest;

class ConfigController extends Controller {

	public function __construct(
		string   $appName,
		IRequest $request,
		private IAppConfig $appConfig,
		?string $userId,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Set admin config values
	 *
	 * @param array $values key/value pairs to store in app config
	 * @return DataResponse
	 * @throws AppConfigTypeConflictException
	 */
	public function setAdminConfig(array $values): DataResponse {
		foreach ($values as $key => $value) {
			$this->appConfig->setValueString(Application::APP_ID, $key, $value);
		}
		return new DataResponse(1);
	}
}
