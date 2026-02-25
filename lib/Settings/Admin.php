<?php

namespace OCA\Replicate\Settings;

use OCA\Replicate\AppInfo\Application;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IAppConfig;
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
		$model = $this->appConfig->getValueString(Application::APP_ID, 'model', 'large', lazy: true);
		$llmModelName = $this->appConfig->getValueString(Application::APP_ID, 'llm_model_name', Application::DEFAULT_LLM_NAME, lazy: true);
		$llmModelVersion = $this->appConfig->getValueString(Application::APP_ID, 'llm_model_version', Application::DEFAULT_LLM_VERSION, lazy: true);
		$llmExtraParams = $this->appConfig->getValueString(Application::APP_ID, 'llm_extra_params', lazy: true);
		$imageGenModelName = $this->appConfig->getValueString(Application::APP_ID, 'igen_model_name', lazy: true);
		$imageGenModelVersion = $this->appConfig->getValueString(Application::APP_ID, 'igen_model_version', Application::DEFAULT_IMAGE_GEN_VERSION, lazy: true);
		$imageGenExtraParams = $this->appConfig->getValueString(Application::APP_ID, 'igen_extra_params', lazy: true);

		$adminConfig = [
			// do not expose the api key to the user
			'api_key' => $apiKey === '' ? '' : 'dummyApiKey',
			'model' => $model,
			'llm_model_name' => $llmModelName,
			'llm_model_version' => $llmModelVersion,
			'llm_extra_params' => $llmExtraParams,
			'igen_model_name' => $imageGenModelName,
			'igen_model_version' => $imageGenModelVersion,
			'igen_extra_params' => $imageGenExtraParams,
		];
		$this->initialStateService->provideInitialState('admin-config', $adminConfig);

		return new TemplateResponse(Application::APP_ID, 'adminSettings');
	}

	public function getSection(): string {
		return 'ai';
	}

	public function getPriority(): int {
		return 10;
	}
}
