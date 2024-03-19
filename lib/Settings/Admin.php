<?php

namespace OCA\Replicate\Settings;

use OCA\Replicate\AppInfo\Application;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\Settings\ISettings;

class Admin implements ISettings {

	public function __construct(
		private IAppConfig $appConfig,
		private IInitialState $initialStateService,
	) {
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm(): TemplateResponse {
		$apiKey = $this->appConfig->getValueString(Application::APP_ID, 'api_key');
		$model = $this->appConfig->getValueString(Application::APP_ID, 'model', 'large');
		$llmModelName = $this->appConfig->getValueString(Application::APP_ID, 'llm_model_name', Application::DEFAULT_LLM_NAME);
		$llmModelVersion = $this->appConfig->getValueString(Application::APP_ID, 'llm_model_version', Application::DEFAULT_LLM_VERSION);

		$adminConfig = [
			'api_key' => $apiKey,
			'model' => $model,
			'llm_model_name' => $llmModelName,
			'llm_model_version' => $llmModelVersion,
		];
		$this->initialStateService->provideInitialState('admin-config', $adminConfig);

		return new TemplateResponse(Application::APP_ID, 'adminSettings');
	}

	public function getSection(): string {
		return 'connected-accounts';
	}

	public function getPriority(): int {
		return 10;
	}
}
